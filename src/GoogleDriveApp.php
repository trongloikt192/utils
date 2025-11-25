<?php
/**
 * Google Drive App Class
 * @author: trongloikt192@gmail.com - 2016.08.17
 * @description: Sử dụng OAuth 2.0 client IDs với type là Other,
 * sẽ có refresh_token truy cập vào gdrive của account mà ko bị expire
 *
 * Step 1: Đăng nhập google account
 * Step 2: Google chuyển sang trang lấy code
 * Step 3: Dùng code truyền vào param trên url.
 * ex: https://vnlinks.net/goauth?code=<code ở đây>
 *
 * Bao gồm các chức năng:
 * getFolderByPath    - Tìm ID Folder theo đường dẫn
 * getFolderByName    - Tìm ID Folder theo tên
 * getListFolders    - Lấy danh sách Folder
 * createFolder    - Tạo Folder
 * updateFile        - Cập nhật lại thông tin File
 * insertFile        - Thêm File
 * deleteFile        - Xóa File
 * moveFile        - Di chuyển File
 * printAbout        - Lấy thông tin User
 *
 * @example: $googleDriveApp = new GoogleDriveApp();
 * $animateFolder = $googleDriveApp->getFolderByPath('videos/animate');
 */

namespace trongloikt192\Utils;

use Exception;
use Google_Client;
use Google_Exception;
use Google_Http_MediaFileUpload;
use Google_Service_Drive;
use Google_Service_Drive_About;
use Google_Service_Drive_DriveFile;
use trongloikt192\Utils\Exceptions\UtilException;

/**
 * Google Drive function support
 */
class GoogleDriveApp
{
    const APPLICATION_NAME = 'Vnlinks.net';
    protected $_CredentialsFile;
    protected $_TokenFile;
    protected $_ClientId;
    protected $_ClientSecret;
    protected $_RedirectUri;
    protected $_AccessToken;
    protected $_Scopes;
    /* @var Google_Client $_Client */
    protected $_Client; // Id của thư mục root
    /* @var Google_Service_Drive $_Service */
    protected $_Service;
    protected $_RootId = 'root';

    /**
     * GoogleDriveApp constructor.
     * @param null $tokenFile : mục đích truy cập nhiều acc, mỗi acc có 1 file token riêng
     * @param $isInit
     * @throws UtilException
     */
    public function __construct($tokenFile = null, $isInit = false)
    {
        // Get your app info from JSON downloaded from google dev console
        $this->_TokenFile       = $tokenFile ?? config('googleauth.token_file');
        $this->_CredentialsFile = config('googleauth.credentials_file');

        $this->_AccessToken  = json_decode(file_get_contents($this->_TokenFile), true);
        $credentials_json    = json_decode(file_get_contents($this->_CredentialsFile), true);
        $this->_ClientId     = $credentials_json['installed']['client_id'];
        $this->_ClientSecret = $credentials_json['installed']['client_secret'];
        $this->_RedirectUri  = reset($credentials_json['installed']['redirect_uris']);

        // Set the scopes you need
        $this->_Scopes = array(
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.appdata',
            'https://www.googleapis.com/auth/drive.scripts',
            'https://www.googleapis.com/auth/drive.apps.readonly',
            'https://www.googleapis.com/auth/drive.readonly',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile'
        );

        $this->_Client = new Google_Client();
        $this->_Client->setApplicationName(self::APPLICATION_NAME);
        $this->_Client->setClientId($this->_ClientId);
        $this->_Client->setClientSecret($this->_ClientSecret);
        $this->_Client->setRedirectUri($this->_RedirectUri);
        $this->_Client->setAccessType('offline');
        // $this->client->setApprovalPrompt('auto');
        $this->_Client->setScopes($this->_Scopes);

        // Return redirect URL for go to google drive authentication page, to get code
        if ($isInit) return;
        if (!file_exists($this->_TokenFile)) {
            throw new UtilException('Please run initialize() first');
        }

        $this->_Client->setAccessToken($this->_AccessToken);

        // Refresh the token if it's expired.
        if ($this->_Client->isAccessTokenExpired()) {
            $this->refreshToken();
        }

        $this->setService($this->_Client);
    }

    /**
     * Initialize Token
     *
     * @param $code
     *
     * @return array
     * @throws UtilException
     */
    public function initialize($code = null)
    {
        // Step 2: The user accepted your access now you need to exchange it.
        // we received the positive auth callback, get the token and store it in session
        if ($code) {
            $authCode = $code;

            // Exchange authorization code for an access token.
            $accessToken = $this->_Client->fetchAccessTokenWithAuthCode($authCode);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new UtilException(implode(', ', $accessToken));
            }

            // Store the credentials to disk.
            if (!file_exists(dirname($this->_TokenFile))
                && !mkdir($concurrentDirectory = dirname($this->_TokenFile), 0700, true)
                && !is_dir($concurrentDirectory)) {
                throw new UtilException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
            file_put_contents($this->_TokenFile, json_encode($accessToken));

            $this->_AccessToken = $accessToken;

            return ['access_token' => $accessToken['access_token']];
        }

        // Redirect to google drive authentication page, to get `code`
        return ['auth_url' => $this->_Client->createAuthUrl()];
    }

    /**
     * Client
     *
     * @return Google_Client
     */
    public function getClient()
    {
        return $this->_Client;
    }

    /**
     * Return access token
     *
     * @return string
     */
    public function getAccessToken()
    {
        $accessToken = $this->_Client->getAccessToken();
        return $accessToken['access_token'];
    }

    /**
     * Return token file path
     *
     * @return string
     */
    public function getTokenFile()
    {
        return $this->_TokenFile;
    }

    /**
     * Service
     *
     * @return Google_Service_Drive
     */
    public function getService()
    {
        return $this->_Service;
    }

    /**
     * @param $client
     */
    public function setService($client)
    {
        $this->_Service = new Google_Service_Drive($client);
    }

    /**
     * Refresh Token & return new access_token
     *
     * @return string
     */
    public function refreshToken()
    {
        $refreshToken = $this->_Client->getRefreshToken();
        $this->_Client->fetchAccessTokenWithRefreshToken($refreshToken);
        $accessToken = $this->_Client->getAccessToken();

        if (!isset($accessToken['refresh_token'])) {
            $accessToken['refresh_token'] = $refreshToken;
        }

        file_put_contents($this->_TokenFile, json_encode($accessToken));

        return $accessToken['access_token'];
    }

    /**
     * Update an existing file's metadata and content.
     *
     * @param string $fileId ID of the file to update.
     * @param string $newTitle New title for the file.
     * @param string $newDescription New description for the file.
     * @param string $newMimeType New MIME type for the file.
     * @return Google_Service_Drive_DriveFile
     *     an API error occurred.
     * @throws Google_Exception|UtilException
     */
    public function updateFile($fileId, $newTitle, $newDescription, $newMimeType)
    {
        $result = false;

        try {
            // First retrieve the file from the API.
            $file = $this->_Service->files->get($fileId);

            // File's new metadata.
            if (isset($newTitle)) {
                $file->setName($newTitle);
            }
            if (isset($newDescription)) {
                $file->setDescription($newDescription);
            }
            if (isset($newMimeType)) {
                $file->setMimeType($newMimeType);
            }

            // Send the request to the API.
            $result = $this->_Service->files->update($fileId, $file);

        } catch (Exception $e) {
            $error = json_decode($e->getMessage(), TRUE);
            if (isset($error['error'])) {
                throw new Google_Exception($error['error']['message'], $error['error']['code']);
            }
            throw new UtilException($e->getMessage());
        }

        return $result;
    }

    /**
     * Insert new file in the Application Data folder.
     *
     * @param string $title Title of the file to insert, including the extension.
     * @param string $description Mô tả
     * @param string $source Source of the file to insert.
     * @param string $path Đường dẫn đặt file
     * @param string $parentId ID thư mục chứa file
     * @return bool
     * @throws Google_Exception|UtilException
     */
    public function insertFile($title, $description, $source, $path = '', $parentId = null)
    {
        $chunkSizeBytes = 10 * 1024 * 1024; // 256 MB
        $sizeUpload     = 0;
        $fileSize       = exec('stat -c %s "' . $source . '"');
        $mimeType       = mime_content_type($source);
        $result         = false;

        try {

            $inFolderId = null;
            $inFolderId = $parentId ?? $this->getFolderByPath($path);

            $file = new Google_Service_Drive_DriveFile();
            $file->setName($title);
            $file->setDescription($description);
            $file->setMimeType($mimeType);

            // Setup the folder you want the file in, if it is wanted in a folder
//			$parent = new Google_Service_Drive_ParentReference();
//			$parent->setId($inFolderId);
            $file->setParents([$inFolderId]);

            set_time_limit(6000);
            ini_set('memory_limit', '512M');

            // Ghi Log đang upload File nào và upload được bao nhiêu MB rồi.
            /*$log = fopen("/var/tmp/log_" . date('Ymd') . ".txt", "a");
            fwrite($log, "=============================\n");
            fwrite($log, $title . "\n");*/

            $options = array(
                'gs' => array(
                    'enable_cache' => false,
                    'acl'          => 'public-read',
                )
            );
            $ctx     = stream_context_create($options);
            if (($handle = fopen($source, 'r', false, $ctx)) === false) {
                throw new UtilException('fopen failed.');
            }

            // Call the API with the media upload, defer so it doesn't immediately return.
            $this->_Client->setDefer(true);
            $request = $this->_Service->files->create($file);

            // Create a media file upload to represent our upload process.
            $media = new Google_Http_MediaFileUpload(
                $this->_Client, // Google_Client
                $request,       // request_api
                $mimeType,      // mimeType
                null,       // data
                true,   // resumable
                $chunkSizeBytes // chunksize
            );
            $media->setFileSize($fileSize);

            // Upload the various chunks. $status will be false until the process is complete.
            $status = false;
            while (!$status && !feof($handle)) {
                $chunk  = $this->readChunk($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);

                // Ghi log % upload processing
                /*$sizeUpload += $chunkSizeBytes;
                fwrite($log, number_format($sizeUpload * 100 / $fileSize) . "%\n");*/
            }

            // The final value of $status will be the data from the API for the object that has been uploaded.
            if ($status != false) {
                $result = $status;
            }

            fclose($handle);
            /*fclose($log);*/

        } catch (Exception $e) {
            $error = json_decode($e->getMessage(), TRUE);
            if (isset($error['error'])) {
                throw new Google_Exception($error['error']['message'], $error['error']['code']);
            }
            throw new UtilException($e->getMessage());

        } finally {
            // Reset to the client to execute requests immediately in the future.
            $this->_Client->setDefer(false);
            set_time_limit(30);
            ini_set('memory_limit', '128M');
        }

        // Return a bunch of data including the link to the file we just uploaded
        return $result;
    }

    /**
     * Tìm thư mục theo đường dẫn
     * nếu ko tìm thấy thì sẽ tự tạo theo cấu trúc giống như đường dẫn
     *
     * @param string $path : Đường dẫn của thư mục cần tìm
     * @return string Id của folder tìm được
     * @throws Google_Exception
     * @example getFolderByPath('videos/thumbnails/def')
     */
    public function getFolderByPath($path)
    {
        $folderId = $this->_RootId;

        // Remove extra slashes and trim the path
        $path     = preg_replace('/^\/*|\/*$/', $path, '');
        $path     = preg_replace('/^\s*|\s*$/', $path, '');
        $fullPath = explode('/', $path);

        // Always start with the main Drive folder
        $inFolderId = null;

        foreach ($fullPath as $key => $name) {
            $folderId   = $this->getFolderByName($name, $inFolderId);
            $inFolderId = $folderId;
        }

        return $folderId;
    }

    /**
     * Tìm thư mục theo tên
     * nếu ko tìm thấy thì sẽ tự tạo
     *
     * @param string $name : Tên thư mục cần tìm
     * @param string $inFolderId : Nằm trong thư mục có Id là inFolderId
     * @return string Id của folder tìm được
     * @throws Google_Exception
     */
    public function getFolderByName($name, $inFolderId = null)
    {

        if (empty($inFolderId)) {
            $inFolderId = $this->_RootId;
        }

        // List all user files (and folders) at inFolderId
        $files    = $this->getListFolders($inFolderId);
        $found    = false;
        $folderId = NULL;

        // Go through each one to see if there is already a folder with the specified name
        foreach ($files as $item) {
            if ($item['name'] == $name) {
                $found    = true;
                $folderId = $item['id'];
                break;
            }
        }

        // If not, create one
        if ($found == false) {
            $folderId = $this->createFolder($name, $inFolderId);
        }

        return $folderId;
    }

    /**
     * Lấy danh sách folder nằm trong inFolder,
     * mặc định sẽ lấy các folder nằm ngoài cùng (root)
     *
     * @param string $inFolderId : ID của folder cần lấy danh sách folder con của nó
     * @return array list folder
     */
    public function getListFolders($inFolderId = null)
    {
        if (empty($inFolderId)) {
            $inFolderId = $this->_RootId;
        }

        $result    = array();
        $pageToken = NULL;
        $filter    = "'{$inFolderId}' in parents"
            . " and mimeType='application/vnd.google-apps.folder'"
            . ' and trashed=false';

        do {
            try {
                $parameters = array('q' => $filter);
                if ($pageToken) {
                    $parameters['pageToken'] = $pageToken;
                }

                $service = $this->getService();
                $files   = $service->files->listFiles($parameters);

                $result    = array_merge($result, $files->getFiles());
                $pageToken = $files->getNextPageToken();

            } catch (Exception $e) {
                print 'An error occurred: ' . $e->getMessage();
                $pageToken = NULL;
            }
        } while ($pageToken);

        return $result;
    }

    /**
     * Tạo thư mục, thư mục này sẽ được public
     * mặc định sẽ tạo folder nằm ngoài cùng (root)
     *
     * @param string $name : Tên thư mục cần tạo
     * @param string $inFolderId : Nằm trong thư mục có Id là inFolderId
     * @return string Id của folder được tạo
     * @throws Google_Exception|UtilException
     */
    public function createFolder($name, $inFolderId = null)
    {
        $result = false;

        if (empty($inFolderId)) {
            $inFolderId = $this->_RootId;
        }

        $folder = new Google_Service_Drive_DriveFile();

        //Setup the folder to create
        $folder->setName($name);
        $folder->setMimeType('application/vnd.google-apps.folder');

        // Set the parent folder.
        if (!empty($inFolderId)) {
//		    $parent = new Google_Service_Drive_ParentReference();
//		    $parent->setId($inFolderId);
            $folder->setParents([$inFolderId]);
        }

        //Create the Folder
        try {

            $createdFile = $this->_Service->files->create($folder, array(
                'mimeType' => 'application/vnd.google-apps.folder'
            ));

            $createdFileId = $createdFile->getId();

            // set permission to Folder
//			$permission = new Google_Service_Drive_Permission();
//		  	$permission->setType('user');
//		  	$permission->setRole('reader');
//		  	$permission->setAllowFileDiscovery(false);

            // $permission->setValue('appsrocks.com');
            // $permission->setType('domain');
            // $permission->setRole('reader');

//		 	$this->_Service->permissions->create($createdFileId, $permission);

            // Return the created folder's id
            $result = $createdFileId;

        } catch (Exception $e) {
            $error = json_decode($e->getMessage(), TRUE);
            if (isset($error['error'])) {
                throw new Google_Exception($error['error']['message'], $error['error']['code']);
            }
            throw new UtilException($e->getMessage());
        }

        return $result;
    }

    /**
     * Permanently delete a file, skipping the trash.
     *
     * @param String $fileId ID of the file to delete.
     * @return bool
     * @throws Google_Exception|UtilException
     */
    public function deleteFile($fileId)
    {
        $result = false;

        try {
            $result = $this->_Service->files->delete($fileId);

        } catch (Exception $e) {
            $error = json_decode($e->getMessage(), TRUE);
            if (isset($error['error'])) {
                throw new Google_Exception($error['error']['message'], $error['error']['code']);
            }
            throw new UtilException($e->getMessage());
        }

        return $result;
    }

    /**
     * Move a file.
     *
     * @param string $fileId ID of the file to move.
     * @param string $newParentId Id of the folder to move to.
     * @return Google_Service_Drive_DriveFile The updated file. NULL is returned if an API error occurred.
     * @throws Google_Exception|UtilException
     */
    public function moveFile($fileId, $newParentId)
    {
        $result = false;

        try {
            $file = new Google_Service_Drive_DriveFile();

//		    $parent = new Google_Service_Drive_ParentReference();
//		    $parent->setId($newParentId);

            $file->setParents([$newParentId]);

            $result = $this->_Service->files->update($fileId, $file);

        } catch (Exception $e) {
            $error = json_decode($e->getMessage(), TRUE);
            if (isset($error['error'])) {
                throw new Google_Exception($error['error']['message'], $error['error']['code']);
            }
            throw new UtilException($e->getMessage());
        }

        return $result;
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function downloadFile($fileId)
    {
        $response = $this->_Service->files->get($fileId, array('alt' => 'media'));
        return $response->getBody()->getContents();
    }

    /**
     * Return download link of fileId
     *
     * @param $fileId
     * @return array
     */
    public function getDownloadUrl($fileId)
    {
        $path        = 'https://www.googleapis.com/drive/v3/files/:fileId?alt=media';
        $accessToken = $this->_Client->getAccessToken();

        $url = str_replace(array(':fileId', ':accessToken'), array($fileId, $accessToken['access_token']), $path);
//        $downloadLink = GetLinkFunction::g_getDirectLink('https://drive.google.com/uc?id='.$fileId.'&export=download&access_token='.$accessToken['access_token']);

        return [
            'Location'      => $url,
            'Authorization' => 'Bearer ' . $accessToken['access_token']
        ];
    }

    /**
     * Return upload link
     * Document: https://developers.google.com/drive/api/v3/reference/files/create
     *
     * @param $fileName
     * @param $fileSize
     * @param $mimeType
     * @return mixed|string
     * @throws Exception
     */
    public function getUploadUrl($fileName, $fileSize, $mimeType)
    {
        $reqURL      = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable';
        $accessToken = $this->_Client->getAccessToken();

        $reqParams  = ['name' => $fileName];
        $reqOptions = [
            'headers'       => [
                'Authorization'           => 'Bearer ' . $accessToken['access_token'],
                'Content-Type'            => 'application/json; charset=UTF-8',
                'X-Upload-Content-Type'   => $mimeType,
                'X-Upload-Content-Length' => $fileSize,
            ],
            'isRequestJson' => true
        ];
        $client     = HttpUtil::g_goutteRequest('POST', $reqURL, $reqParams, null, $reqOptions);
        $signedURL  = $client->getResponse()->getHeader('Location');

        // when server not found
        if ($signedURL === null) {
            throw new UtilException('Server not found');
        }

        return $signedURL;
    }

    /**
     * Print information about the current user along with the Drive API settings.
     *
     * @return array
     */
    public function getAbout()
    {
        $result = [];

        /** @var Google_Service_Drive_About $about */
        $about                 = $this->_Service->about->get(['fields' => 'user, storageQuota, maxUploadSize']);
        $result['name']        = $about->getUser()->getDisplayName();
        $result['email']       = $about->getUser()->getEmailAddress();
        $result['expire_at']   = date('y-m-d H:i:s e', $this->_AccessToken['created'] + $this->_AccessToken['expires_in']);
        $result['used_quota']  = number_format($about->getStorageQuota()->getUsage() / (1024 * 1024 * 1024), 2) . ' (GB)';
        $result['total_quota'] = number_format($about->getStorageQuota()->getLimit() / (1024 * 1024 * 1024), 2) . ' (GB)';

        return $result;
    }

    /**
     * Print information about the file
     *
     * @param $fileId
     * @return array
     * https://developers.google.com/drive/api/v3/reference/files#resource
     */
    public function getFileInfo($fileId)
    {
        $path        = 'https://www.googleapis.com/drive/v3/files/:fileId?access_token=:accessToken';
        $accessToken = $this->_Client->getAccessToken();

        $reqURL = str_replace(array(':fileId', ':accessToken'), array($fileId, $accessToken['access_token']), $path);
        $data   = HttpUtil::g_curlGet($reqURL);

        return json_decode($data, true);
    }

    /**
     * Logout google account
     */
    public function logout()
    {
        unlink($this->_TokenFile);
    }

    /**
     * Read chunk for insert lager file
     * @param $fp
     * @param $chunkSize
     * @return string
     */
    private function readChunk($fp, $chunkSize)
    {
        $ret            = '';
        $bytesRemaining = $chunkSize;
        $bytesRead      = 0;
        while (!feof($fp) && $bytesRemaining > 0) {
            $buffer         = fread($fp, $bytesRemaining);
            $ret            .= $buffer;
            $bytesRead      += strlen($buffer);
            $bytesRemaining = $chunkSize - $bytesRead;
        }

        return $ret;
    }

    /**
     * Save gdrive token to file temp
     *
     * @param $tokenString
     * @return string
     */
    public static function saveTokenFile($tokenString): string
    {
        $tokenFilePath = tempnam(sys_get_temp_dir(), 'gdrive-token-');
        file_put_contents($tokenFilePath, $tokenString);

        return $tokenFilePath;
    }
}
