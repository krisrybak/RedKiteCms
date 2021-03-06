<?php
/*
 * This file is part of the RedKite CMS Application and it is distributed
 * under the MIT LICENSE. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) RedKiteCms <webmaster@redkite-labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.redkite-labs.com
 *
 * @license    MIT LICENSE
 *
 */

namespace RedKiteLabs\RedKiteCms\InstallerBundle\Core\Installer\Base;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Filesystem\Filesystem;
use RedKiteLabs\RedKiteCms\InstallerBundle\Core\Validator\Validator;

/**
 * Implements a base class to define the base options required to install RedKite Cms
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
abstract class BaseOptions
{
    protected $kernelDir;
    protected $vendorDir;
    protected $options;
    protected $bundleName;
    protected $database;
    protected $driver;
    protected $host;
    protected $port;
    protected $user;
    protected $password;
    protected $dsnBuilder;
    protected $prerequisitesVerified = null;
    private $log = array();
    
    /**
     * Contructor
     * 
     * @param array $options
     */
    public function __construct($kernelDir, array $options = array())
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
        
        $this->kernelDir = $this->normalizePath($kernelDir);
        $this->vendorDir = $this->kernelDir . '/../vendor';
        $this->bundleName = Validator::validateBundleName($this->options["bundle"]);        
        $this->deployBundle = $this->bundleName;        
        if (empty($this->deployBundle) || !preg_match('/.*?Bundle$/', $this->deployBundle)) { 
            throw new \InvalidArgumentException("Something was wrong with the values you entered to define the deploy bundle. Please refer to http://redkite-labs.com/how-to-install-redkite-cms#the-deploy-bundle to learn more about this topic.");
        }
        $this->driver = Validator::validateDriver($this->options["driver"]);
        $this->host = Validator::validateHost($this->options["host"]);
        $this->database = Validator::validateDatabaseName($this->options["database"]);
        if ($this->driver != 'sqlite') {
            $this->port = Validator::validatePort((int)$this->options["port"]);
            $this->user = Validator::validateUser($this->options["user"]);
            $this->password = $this->options["password"];
        }
        $this->websiteUrl = Validator::validateUrl($this->options["website-url"]);
        
        $dsnBuilderClassName = '\RedKiteLabs\RedKiteCms\InstallerBundle\Core\DsnBuilder\GenericDsnBuilder';
        $specificDsnBuilderClassName = '\RedKiteLabs\RedKiteCms\InstallerBundle\Core\DsnBuilder\\' . ucfirst($this->driver) . 'DsnBuilder';
        if (class_exists($specificDsnBuilderClassName)) {
            $dsnBuilderClassName = $specificDsnBuilderClassName;
        }
        $this->dsnBuilder = new $dsnBuilderClassName($this->options);
        
        $this->filesystem = new Filesystem();
    }

    public function getLogMessages()
    {
        return $this->log;
    }

    public function getLogMessagesRaw()
    {
        return array_map(function($message){ return strip_tags($message); }, $this->log());
    }
    
    protected function checkWritePermissions()
    {
        $files = array(
            $this->kernelDir . '/../' => 'folder',
            $this->kernelDir . '/cache' => 'folder',
            $this->kernelDir . '/logs' => 'folder',
            $this->kernelDir . '/config' => 'folder',            
            $this->kernelDir . '/config/bundles' => 'folder',
            $this->kernelDir . '/../web' => 'folder',
            $this->kernelDir . '/AppKernel.php' => 'file',                
            $this->kernelDir . '/config/config.yml' => 'file',           
            $this->kernelDir . '/config/routing.yml' => 'file',       
            $this->kernelDir . '/config/parameters.yml' => 'file',    
        );

        $this->log = array();
        foreach($files as $filename => $type) {
            if (file_exists($filename) && ! is_writeable($filename)) {
                $this->log[] = '<comment>' . realpath($filename) . '</comment> cannot be written. Please fix permissions on this ' . $type;
            }
        }

        return empty($this->log);
    }
    
    /**
     * Checks that RedKite CMS prerequisites are satisfied
     * 
     * @throws \RuntimeException
     */
    protected function checkPrerequisites()
    {
        if (null !== $this->prerequisitesVerified) {
            return;
        }

        $this->checkClass('propel', '\Propel');
        $this->checkFolder($this->vendorDir . '/phing');
        $this->checkClass('PropelBundle', 'Propel\PropelBundle\PropelBundle');

        Validator::validateDeployBundle($this->kernelDir, $this->bundleName);
        
        $this->prerequisitesVerified = true;
    }
    
    
    
    /**
     * Defines the required/optional options
     * 
     * @param \Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('bundle'));
        $resolver->setRequired(array('host'));
        $resolver->setRequired(array('driver'));
        $resolver->setRequired(array('port'));
        $resolver->setRequired(array('database'));
        $resolver->setRequired(array('user'));
        $resolver->setOptional(array('password'));
        $resolver->setRequired(array('website-url'));
    }

    /**
     * Normalize a path as a unix path
     *
     * @param   string      $path
     * @return  string
     */
    private function normalizePath($path)
    {
        return preg_replace('/\\\/', '/', $path);
    }
    
    private function checkClass($libraryName, $className)
    {
        if(!class_exists($className))
        {
            $message = "\nAn error occurred. RedKite CMS requires the " . $libraryName . " library. Please install that library then run the script again.\n";

            throw new \RuntimeException($message);
        }
    }

    private function checkFolder($dirName)
    {
        if(!is_dir($dirName))
        {
            $message = "\nAn error occurred. RedKite CMS requires " . basename($dirName) . " installed into " . dirname($dirName) . " folder. Please install the required library then run the script again.\n";

            throw new \RuntimeException($message);
        }
    }
}
