<?php

namespace Charcoal\App\ServiceProvider;

use Exception;
use InvalidArgumentException;
use UnexpectedValueException;

// Dependencies from `pimple/pimple`
use Pimple\ServiceProviderInterface;
use Pimple\Container;

// Dependencies from AWS S3 SDK
use Aws\S3\S3Client;

// Dependencies from Dropbox
use Dropbox\Client as DropboxClient;

// Dependencies from `league/flysystem`
use League\Flysystem\MountManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Adapter\NullAdapter;

// Dependency from `league/flysystem-aws-s3-v3`
use League\Flysystem\AwsS3v3\AwsS3Adapter;

// Dependency from `league/flysystem-dropbox`
use League\Flysystem\Dropbox\DropboxAdapter;

// Dependency from `league/flysystem-sftp`
use League\Flysystem\Sftp\SftpAdapter;

// Dependency from `league/flysystem-memory`
use League\Flysystem\Memory\MemoryAdapter;

// Local namespace depdency
use Charcoal\App\Config\FilesystemConfig;

/**
 *
 */
class FilesystemServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container Pimple DI Container.
     * @return void
     */
    public function register(Container $container)
    {
        /**
         * @param Container $container Pimple DI Container.
         * @return FlysystemConfig
         */
        $container['filesystem/config'] = function (Container $container) {
            $appConfig = $container['config'];

            return new FilesystemConfig($appConfig['filesystem']);
        };

        /**
         * @param Container $container Pimple DI Container.
         * @return MountManager
         */
        $container['filesystem/manager'] = function () {
            return new MountManager();
        };

        $container['filesystems'] = function (Container $container) {
            $filesystemConfig = $container['filesystem/config'];
            $filesystems = new Container();

            foreach ($filesystemConfig['connections'] as $ident => $connection) {
                $fs = $this->createConnection($connection);
                $filesystems[$ident] = $fs;
                $container['filesystem/manager']->mountFilesystem($ident, $fs);
            }

            return $filesystems;
        };
    }

    /**
     * @param array $config The driver (adapter) configuration.
     * @throws Exception If the filesystem type is not defined in config.
     * @throws UnexpectedValueException If the filesystem type is invalid / unsupported.
     * @return Filesystem
     */
    private function createConnection(array $config)
    {
        if (!isset($config['type'])) {
            throw new Exception(
                'No filesystem type defined'
            );
        }

        $type = $config['type'];

        if ($type == 'local') {
            $adapter = $this->createLocalAdapter($config);
        } elseif ($type == 's3') {
            $adapter = $this->createS3Adapter($config);
        } elseif ($type == 'dropbox') {
            $adapter = $this->createDropboxAdapter($config);
        } elseif ($type == 'ftp') {
            $adapter = $this->createFtpAdapter($config);
        } elseif ($type == 'sftp') {
            $adapter = $this->createSftpAdapter($config);
        } elseif ($type == 'memory') {
            $adapter = $this->createMemoryAdapter($config);
        } elseif ($type == 'noop') {
            $adapter = $this->createNullAdapter($config);
        } else {
            throw new UnexpectedValueException(
                sprintf('Invalid filesystem type "%s"', $type)
            );
        }

        return new Filesystem($adapter);
    }

    /**
     * @param array $config The driver (adapter) configuration.
     * @throws InvalidArgumentException If the path is not defined in config.
     * @return LocalAdapter
     */
    private function createLocalAdapter(array $config)
    {
        if (!isset($config['path']) || !$config['path']) {
            throw new InvalidArgumentException(
                'No "path" configured for local filesystem.'
            );
        }
        $defaults = [
            'lock'        => null,
            'links'       => null,
            'permissions' => []
        ];
        $config = array_merge($defaults, $config);

        return new LocalAdapter($config['path'], $config['lock'], $config['links'], $config['permissions']);
    }

    /**
     * @param array $config The driver (adapter) configuration.
     * @throws InvalidArgumentException If the key, secret or bucket is not defined in config.
     * @return AwsS3Adapter
     */
    private function createS3Adapter(array $config)
    {
        if (!isset($config['key']) || !$config['key']) {
            throw new InvalidArgumentException(
                'No "key" configured for S3 filesystem.'
            );
        }
        if (!isset($config['secret']) || !$config['secret']) {
            throw new InvalidArgumentException(
                'No "secret" configured for S3 filesystem.'
            );
        }

        if (!isset($config['bucket']) || !$config['bucket']) {
            throw new InvalidArgumentException(
                'No "bucket" configured for S3 filesystem.'
            );
        }

        $defaults = [
            'region'  => '',
            'version' => 'latest',
            'prefix'  => null
        ];
        $config = array_merge($defaults, $config);

        $client = S3Client::factory([
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
            'region'      => $config['region'],
            'version'     => $config['version'],
        ]);

        if (isset($config['public']) && !$config['public']) {
            $permissions = null;
        } else {
            $permissions = [
                'ACL' => 'public-read'
            ];
        }

        return new AwsS3Adapter($client, $config['bucket'], $config['prefix'], $permissions);
    }

    /**
     * @param array $config The driver (adapter) configuration.
     * @throws InvalidArgumentException If the token or secret is not defined in config.
     * @return FtpAdapter
     */
    private function createDropboxAdapter(array $config)
    {
        if (!isset($config['token']) || !$config['token']) {
            throw new InvalidArgumentException(
                'No access "token" configured for dropbox filesystem adapter.'
            );
        }

        if (!isset($config['secret']) || !$config['secret']) {
            throw new InvalidArgumentException(
                'No app "secret" configured for dropbox filesystem adapter.'
            );
        }

        $defaults = [
            'prefix' => ''
        ];
        $config = array_merge($defaults, $config);

        $client = new DropboxClient($config['token'], $config['secret']);
        return new DropboxAdapter($client, $config['prefix']);
    }

    /**
     * @param array $config The driver (adapter) configuration.
     * @throws InvalidArgumentException If the host, username or password is not defined in config.
     * @return FtpAdapter
     */
    private function createFtpAdapter(array $config)
    {
        if (!$config['host']) {
            throw new InvalidArgumentException(
                'No host configured for FTP filesystem filesystem adapter.'
            );
        }
        if (!$config['username']) {
            throw new InvalidArgumentException(
                'No username configured for FTP filesystem filesystem adapter.'
            );
        }
        if (!$config['password']) {
            throw new InvalidArgumentException(
                'No password configured for FTP filesystem filesystem adapter.'
            );
        }

        $defaults = [
            'port'    => null,
            'root'    => null,
            'passive' => null,
            'ssl'     => null,
            'timeout' => null
        ];
        $config = array_merge($defaults, $config);

        return new FtpAdapter($config);
    }

    /**
     * @param array $config The driver (adapter) configuration.
     * @throws InvalidArgumentException If the host, username or password is not defined in config.
     * @return SftpAdapter
     */
    private function createSftpAdapter(array $config)
    {
        if (!$config['host']) {
            throw new InvalidArgumentException(
                'No host configured for SFTP filesystem filesystem adapter.'
            );
        }
        if (!$config['username']) {
            throw new InvalidArgumentException(
                'No username configured for SFTP filesystem filesystem adapter.'
            );
        }
        if (!$config['password']) {
            throw new InvalidArgumentException(
                'No password configured for SFTP filesystem filesystem adapter.'
            );
        }

        $defaults = [
            'port'       => null,
            'privateKey' => null,
            'root'       => null,
            'timeout'    => null
        ];
        $config = array_merge($defaults, $config);

        return new SftpAdapter($config);
    }

    /**
     * @return MemoryAdapter
     */
    private function createMemoryAdapter()
    {
        return new MemoryAdapter();
    }

    /**
     * @return NullAdapter
     */
    private function createNullAdapter()
    {
        return new NullAdapter();
    }
}
