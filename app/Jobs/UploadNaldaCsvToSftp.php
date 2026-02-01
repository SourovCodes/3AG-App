<?php

namespace App\Jobs;

use App\Enums\NaldaCsvType;
use App\Models\NaldaCsvUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Net\SFTP;

class UploadNaldaCsvToSftp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public NaldaCsvUpload $csvUpload,
        public string $encryptedPassword
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $csvFile = $this->csvUpload->getCsvFile();

        if (! $csvFile) {
            $this->csvUpload->markAsFailed('CSV file not found.');

            return;
        }

        $localFilePath = $csvFile->getPath();
        $originalFilename = $csvFile->file_name;

        try {
            $password = Crypt::decryptString($this->encryptedPassword);

            $sftpPath = $this->uploadToSftp(
                $this->csvUpload->sftp_host,
                $this->csvUpload->sftp_port,
                $this->csvUpload->sftp_username,
                $password,
                $this->csvUpload->csv_type,
                $localFilePath,
                $originalFilename
            );

            $this->csvUpload->markAsUploaded($sftpPath);
        } catch (\Exception $e) {
            $this->csvUpload->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    private function uploadToSftp(
        string $host,
        int $port,
        string $username,
        string $password,
        NaldaCsvType $csvType,
        string $localFilePath,
        string $originalFilename
    ): string {
        $sftp = new SFTP($host, $port, 30);

        try {
            if (! $sftp->login($username, $password)) {
                throw new \RuntimeException('SFTP authentication failed.');
            }

            $remoteFolder = $csvType->getSftpFolder();
            $remotePath = rtrim($remoteFolder, '/').'/'.$originalFilename;

            if ($remoteFolder !== '/') {
                $sftp->mkdir($remoteFolder, -1, true);
            }

            if (! $sftp->put($remotePath, $localFilePath, SFTP::SOURCE_LOCAL_FILE)) {
                throw new \RuntimeException('Failed to upload file.');
            }

            return $remotePath;
        } finally {
            $sftp->disconnect();
        }
    }
}
