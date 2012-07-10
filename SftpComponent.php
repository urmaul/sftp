<?php
require_once(dirname(__FILE__) . '/Net/SFTP.php');

/**
 * Simple component for handling SFTP functionalities
 *
 * @author	Aruna Attanayake <aruna470@gmail.com>
 * @version 1.2
 */

class SftpComponent extends CApplicationComponent
{
	/**
     * @var string $host sftp host ip.
     */ 
	public $host = null;
	 
	/**
     * @var string $port sftp host port default 22.
     */
	public $port = '22';
	
	/**
     * @var string $username username of remote sftp account.
     */
	public $username = null;
	
	/**
     * @var string $username username of remote sftp account.
     */
	public $password = null;
			
	/**
     * @var SFTP $objSftp sftp class object.
     */
	private $objSftp = null;
	
	/**
	 * @var SSH2 $objSsh SSH class object.
	 */
	private $objSsh = null;
	
	/**
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string $port
	 */
	public function __construct($host = null, $username = null, $password = null, $port = '22')
	{
      	$this->host = $host;
      	$this->username = $username;
     	$this->password = $password;
      	$this->port = $port;
	}
	
	/**
	 * Initializes the component.
	 */
	public function init()
	{
		parent::init();
	}
	
	/**
	 * Establish SFTP connection
	 * @return bool true when connection success
	 * @throws CException if connection fails
	 */
	public function connect() 
	{
		$this->objSftp = new Net_SFTP($this->host);
		
		if ($this->objSftp->login($this->username, $this->password)) 
		{
			$this->objSsh = new Net_SSH2($this->host);			
			$this->objSsh->login($this->username, $this->password);
						
		    return true;
		}
		else
		{
			throw new CException('Invalid username or password.');
		}		
	}
	
	/**
	 * list directory contents
	 * @param string $directory Directory path
	 * @param bool $showHiddenFiles default false, if true list hidden files also
	 * @return array $files list of contents including directories
	 */
	public function listFiles($directory = '.', $showHiddenfiles = false)
	{
		$res_files = $this->objSftp->nlist($directory);
	
		$files = array();
		
		foreach($res_files as $file)
		{
			if (!$showHiddenfiles && ('.' == $file || '..' == $file || '.' == $file[0]))
				continue;
			
			$files[] = $file;
		}
		
		return $files;
	}

	/**
	 * Returns the current directory
	 * @return string Current directory path
	 */
	public function getCurrentDir()
	{
		return $this->objSftp->pwd();
	}

	/**
	 * Check for directory
	 * @param string $directory Directory path
	 * @return bool true if is a directory otherwise false
	 */
	public function isDir($directory)
	{
		if ($this->objSftp->chdir($directory))
        {
        	$this->objSftp->chdir('..');
            return true;
        } 

        return false;
	}

	/**
	 * Change directory
	 * @param string $directory Directory path
	 * @return bool true if directory change success
	 * @throws CException if directory change fails
	 */
	public function chdir($directory)
	{
		if ($this->objSftp->chdir($directory))
        {
            return true;
        } 
        else
        {
        	throw new CException('Directory change failed.');
        }
	}

	/**
	 * Put file to a sftp location
	 * @param string $localFile Local file path
	 * @param string $remoteFile Remote file path
	 * @return bool true if file send success
	 * @throws CException if file transfer fails
	 */
	public function sendFile($localFile, $remoteFile)
	{
		if ($this->objSftp->put($remoteFile, $localFile, NET_SFTP_LOCAL_FILE)) 
		{
			return true;
		} 
		else 
		{
			throw new CException('File send failed.');
		}
	}

	/**
	 * Put file to a sftp location
	 * @param string $remoteFile Remote file path
	 * @param string $data file contents
	 * @return bool true if file send success
	 * @throws CException if file transfer fails
	 */
	public function writeFile($remoteFile, $data)
	{
		if ($this->objSftp->put($remoteFile, $data, NET_SFTP_STRING)) 
		{
			return true;
		} 
		else 
		{
			throw new CException('File send failed.');
		}
	}
	
	/**
	 * Get file from sftp location
	 * @param string $remoteFile Remote file path
	 * @param string $localFile Local file path
	 * @return bool true if file retreival success
	 * @throws CException if file transfer fails
	 */
	public function getFile($remoteFile, $localFile) 
	{
		if ($this->objSftp->get($remoteFile, $localFile)) 
		{
			return true;
		} 
		else 
		{
			throw new CException('File get failed.');
		}
	}
	
	/**
	 * Reads file from sftp location
	 * @param string $remoteFile Remote file path
	 * @return string remote file contents
	 * @throws CException if file transfer fails
	 */
	public function readFile($remoteFile)
	{
		$data = $this->objSftp->get($remoteFile, false);
        if ($data !== false) 
		{
			return $data;
		} 
		else 
		{
			throw new CException('File get failed.');
		}
	}
	 
	/**
	 * Retreive file attributes 
	 * @param string $file Remote file path
	 * @param string $attribute Required attribute (size, gid, uid, atime, mtime, mode)
	 * @return string Attribute value
	 */
	private function getFileStat($file, $attribute)
	{
		$statinfo = $this->objSftp->stat($file);
		
		return $statinfo[$attribute];
	}
	
	/**
	 * Retreive file size 
	 * @param string $file Remote file path
	 * @return string File size
	 */
	function getSize($file)
	{
		return $this->getFileStat($file, 'size');
	}
	
	/**
	 * Retreive file modified datetime 
	 * @param string $file Remote file path
	 * @return string File modified timestamp
	 */
	function getMdtm($file)
	{
		return $this->getFileStat($file, 'mtime');
	}
	
	/**
	 * Retreive file created datetime 
	 * @param string $file Remote file path
	 * @return string File created timestamp
	 */
	function getAtime($file)
	{
		return $this->getFileStat($file, 'atime');
	}
	
	/**
	 * Create directory on sftp location
	 * @param string $directory Remote directory path
	 * @return bool true if directory creation success
	 * @throws CException if directory creation fails
	 */
	function createDirectory($directory)
	{
		if ($this->objSftp->mkdir($directory))
		{
			return true;
		}
		else
		{ 
			throw new CException('Directory creation failed.');
		}
	}
	
	/**
	 * Remove directory on sftp location
	 * @param string $directory Remote directory path
	 * @param bool $foreceRemove If true remove directory even it is not empty
	 * @return bool true if directory removal success
	 * @throws CException if directory removal fails
	 */
	function removeDirectory($directory, $foreceRemove=false)
	{
		if ($foreceRemove)
		{
			$this->execCmd("rm -rf {$directory}");
			
			return true;
		}
		else
		{
			if ($this->objSftp->delete($directory))
			{
				return true;
			}
			else
			{ 
				throw new CException('Directory removal failed.');
			}
		}
	}
		
	/**
	 * Remove file on sftp location
	 * @param string $file Remote file path
	 * @return bool true if file removal success
	 * @throws CException if file removal fails
	 */
	function removeFile($file, $foreceRemove=false)
	{
		if ($foreceRemove)
		{
			$this->execCmd("rm -rf {$file}");
			
			return true;
		}
		else
		{
			if ($this->objSftp->delete($file))
			{
				return true;
			}
			else
			{ 
				throw new CException('File removal failed.');
			}
		}
	}
	
	/**
	 * Change directory ownership
	 * @param string $path Directory or file path
	 * @param string $user User
	 * @param string $group Group
	 * @param bool $recursive Change ownership to subcontens also
	 * @return bool true 
	 */
	function chown($path, $user, $group, $recursive=false)
	{
		if ($recursive)
		{
			$cmd = "chown -R {$user}:{$group} {$path}";
		}
		else
		{		
			$cmd = "chown {$user}:{$group} {$path}";		
		}
		
		$this->execCmd($cmd);

		return true;
	}
	
	/**
	 * Change directory permission
	 * @param string $path Directory or file path
	 * @param string $permission Permission
	 * @param bool $recursive Change permission to subcontens also
	 * @return bool true 
	 */
	function chmod($path, $permission, $recursive=false)
	{
		if ($recursive)
		{
			$cmd = "chmod -R {$permission} {$path}";
		}
		else
		{		
			$cmd = "chmod {$permission} {$path}";		
		}
		
		$this->execCmd($cmd);

		return true;
	}
	
	/**
	 * Execute command on remote shell
	 * @param string $cmd Command ex:pwd
	 * @return string $output Command output
	 */
	function execCmd($cmd)  
	{
		$output = $this->objSsh->exec($cmd);
		
		return $output;
	}
}
?>