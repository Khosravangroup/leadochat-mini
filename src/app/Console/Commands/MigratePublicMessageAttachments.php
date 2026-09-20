<?php

namespace App\Console\Commands;

use App\Models\MessageAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MigratePublicMessageAttachments extends Command
{
    protected $signature = 'attachments:migrate-private {--dry-run : Report eligible attachments without changing data}';

    protected $description = 'Move locally stored message attachments from the public disk to private storage';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $eligibleCount = 0;

            foreach (MessageAttachment::query()->lazyById(100) as $attachment) {
                if ($this->isEligible($attachment)) {
                    $eligibleCount++;
                }
            }

            $this->info($eligibleCount.' attachment(s) eligible for private-storage migration.');

            return self::SUCCESS;
        }

        $migrated = 0;
        $failed = 0;

        foreach (MessageAttachment::query()->lazyById(100) as $attachment) {
            if (! $this->isEligible($attachment)) {
                continue;
            }

            try {
                $this->migrateAttachment($attachment);
                $migrated++;
            } catch (\Throwable $exception) {
                $failed++;
                $this->error('Attachment '.$attachment->id.' failed: '.$exception->getMessage());
            }
        }

        $this->info(($migrated + $failed).' attachment(s) eligible for private-storage migration.');
        $this->info($migrated.' attachment(s) migrated; '.$failed.' failed.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function isEligible(MessageAttachment $attachment): bool
    {
        $meta = is_array($attachment->meta) ? $attachment->meta : [];
        $path = (string) ($meta['path'] ?? '');

        return ($meta['disk'] ?? null) === 'public'
            && str_starts_with($path, 'message-attachments/')
            && ! str_contains($path, '..');
    }

    private function migrateAttachment(MessageAttachment $attachment): void
    {
        $meta = is_array($attachment->meta) ? $attachment->meta : [];
        $path = (string) $meta['path'];
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $sourceExists = $public->exists($path);
        $targetExists = $private->exists($path);

        if (! $sourceExists && ! $targetExists) {
            throw new RuntimeException('source and private target are both missing');
        }

        if ($sourceExists && ! $targetExists) {
            $stream = $public->readStream($path);

            if (! is_resource($stream)) {
                throw new RuntimeException('unable to read the public source');
            }

            try {
                if (! $private->writeStream($path, $stream)) {
                    throw new RuntimeException('unable to write the private target');
                }
            } finally {
                fclose($stream);
            }
        }

        if (
            $sourceExists
            && hash_file('sha256', $public->path($path)) !== hash_file('sha256', $private->path($path))
        ) {
            throw new RuntimeException('private target checksum does not match the public source');
        }

        if ($sourceExists) {
            $public->delete($path);

            if ($public->exists($path)) {
                throw new RuntimeException('public source could not be removed');
            }
        }

        $meta['disk'] = 'local';

        $attachment->forceFill([
            'url' => null,
            'meta' => $meta,
        ])->save();
    }
}
