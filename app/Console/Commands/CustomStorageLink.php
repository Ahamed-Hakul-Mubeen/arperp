<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CustomStorageLink extends Command
{
     protected $signature = 'custom:storage-link';
    protected $description = 'Create a symbolic link to the base storage folder';

    public function handle()
    {
        $target = storage_path();
        $link = public_path('storage');

        if (file_exists($link)) {
            $this->error('The "public/storage" directory already exists.');
            return;
        }

        symlink($target, $link);

        $this->info('The "storage" directory has been linked to "public/storage".');
    }
}
