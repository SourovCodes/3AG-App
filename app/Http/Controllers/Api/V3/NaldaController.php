<?php

namespace App\Http\Controllers\Api\V3;

use App\Enums\NaldaCsvType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V3\Nalda\ListCsvUploadsRequest;
use App\Http\Requests\Api\V3\Nalda\UploadCsvRequest;
use App\Http\Requests\Api\V3\Nalda\ValidateSftpRequest;
use App\Http\Resources\Api\V3\NaldaCsvUploadResource;
use App\Models\License;
use App\Models\NaldaCsvUpload;
use Illuminate\Http\JsonResponse;
use phpseclib3\Net\SFTP;

class NaldaController extends Controller
{
    /**
     * Upload a CSV file to SFTP server and store backup in cloud storage.
     */
    public function uploadCsv(UploadCsvRequest $request): JsonResponse
    {
        /** @var License $license */
        $license = $request->input('validated_license');
        $normalizedDomain = $request->input('normalized_domain');

        $csvType = NaldaCsvType::from($request->validated('csv_type'));
        $file = $request->file('csv_file');
        $originalFilename = $file->getClientOriginalName();

        $csvUpload = NaldaCsvUpload::create([
            'license_id' => $license->id,
            'domain' => $normalizedDomain,
            'csv_type' => $csvType,
            'sftp_host' => $request->validated('sftp_host'),
            'sftp_port' => $request->validated('sftp_port') ?? 22,
            'sftp_username' => $request->validated('sftp_username'),
            'status' => 'processing',
        ]);

        $csvUpload->addMedia($file)
            ->usingFileName($this->generateFilename($csvUpload, $originalFilename))
            ->toMediaCollection('csv');

        try {
            $sftpPath = $this->uploadToSftp(
                $request->validated('sftp_host'),
                $request->validated('sftp_port') ?? 22,
                $request->validated('sftp_username'),
                $request->validated('sftp_password'),
                $csvType,
                $csvUpload->getCsvFile()->getPath(),
                $originalFilename
            );

            $csvUpload->markAsUploaded($sftpPath);

            return response()->json([
                'data' => new NaldaCsvUploadResource($csvUpload),
            ], 201);
        } catch (\Exception $e) {
            $csvUpload->markAsFailed($e->getMessage());

            return response()->json([
                'message' => 'Failed to upload to SFTP server. Please check your credentials and try again.',
            ], 500);
        }
    }

    /**
     * List previous CSV upload requests with pagination.
     */
    public function listCsvUploads(ListCsvUploadsRequest $request): JsonResponse
    {
        /** @var License $license */
        $license = $request->input('validated_license');
        $normalizedDomain = $request->input('normalized_domain');

        $uploads = NaldaCsvUpload::query()
            ->with('media')
            ->where('license_id', $license->id)
            ->where('domain', $normalizedDomain)
            ->latest()
            ->paginate($request->validated('per_page') ?? 15);

        return response()->json([
            'data' => NaldaCsvUploadResource::collection($uploads),
            'meta' => [
                'current_page' => $uploads->currentPage(),
                'last_page' => $uploads->lastPage(),
                'per_page' => $uploads->perPage(),
                'total' => $uploads->total(),
            ],
        ]);
    }

    /**
     * Validate SFTP credentials without uploading a file.
     */
    public function validateSftp(ValidateSftpRequest $request): JsonResponse
    {
        try {
            $sftp = new SFTP(
                $request->validated('sftp_host'),
                $request->validated('sftp_port') ?? 22,
                10 // 10 second connection timeout
            );

            if (! $sftp->login($request->validated('sftp_username'), $request->validated('sftp_password'))) {
                return response()->json([
                    'message' => 'Authentication failed.',
                ], 422);
            }

            $sftp->disconnect();

            return response()->json(['data' => []]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Connection failed. Please check the hostname and port.',
            ], 422);
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
        $sftp = new SFTP($host, $port, 30); // 30 second connection timeout

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

        $sftp->disconnect();

        return $remotePath;
    }

    private function generateFilename(NaldaCsvUpload $upload, string $originalFilename): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'csv';

        return sprintf('%s_%s_%d.%s', $upload->csv_type->value, now()->format('Ymd_His'), $upload->id, $extension);
    }
}
