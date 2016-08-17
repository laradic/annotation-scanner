<?php
namespace Codex\Addons;

use Codex\Addons\Scanner\ClassFileInfo;
use Codex\Addons\Scanner\ClassInspector;
use Codex\Addons\Scanner\AnnotationScanner as AnnotationScanner;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Laradic\Filesystem\Filesystem;
use Laradic\Support\Util;
use Symfony\Component\Finder\SplFileInfo;

abstract class Scanner
{

    /** @var \Laradic\Filesystem\Filesystem */
    protected $fs;

    /** @var \Doctrine\Common\Annotations\AnnotationReader */
    protected $reader;

    /**
     * Scanner constructor.
     *
     * @param \Codex\Addons\Factory                         $addons
     * @param \Codex\Addons\Manifest                        $manifest
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader
     * @param \Sebwite\Filesystem\Filesystem                $fs
     */
    public function __construct(AnnotationReader $reader, Filesystem $fs)
    {
        $this->fs       = new Filesystem();
        $this->reader   = new AnnotationReader();
    }

    /**
     * An array of all annotation classes
     *
     * @return array
     */
    abstract public function getAnnotations();

    public function registerAnnotation($filePath)
    {
        AnnotationRegistry::registerFile($filePath);
    }


    public function scanDirectory($path)
    {
        $files = [ ];
        foreach ( $this->createAnnotationScanner($this->getAnnotations())->in($path) as $file )
        {
            /** @var ClassFileInfo $file */
            $files[$file->getClassName()] = $file;
        }
        return $files;
    }

    public function scanFile($path)
    {
        $className = Util::getClassNameFromFile($path);
        $file      = new SplFileInfo($path, $path, $path);
        $inspector = new ClassInspector($className, $this->reader);
        return new ClassFileInfo($file, $inspector);
    }

    protected function createAnnotationScanner($annotationClass)
    {
        if ( ! is_array($annotationClass) )
        {
            $annotationClass = [ $annotationClass ];
        }
        $scanner = new AnnotationScanner($this->reader);
        return $scanner->annotations($annotationClass);
    }

}
