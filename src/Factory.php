<?php
namespace Laradic\AnnotationScanner;

use Laradic\AnnotationScanner\Scanner\ClassFileInfo;
use Laradic\AnnotationScanner\Scanner\ClassInspector;
use Laradic\AnnotationScanner\Scanner\AnnotationScanner as AnnotationScanner;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Laradic\Filesystem\Filesystem;
use Laradic\Support\Util;
use Symfony\Component\Finder\SplFileInfo;

class Factory
{

    /** @var \Laradic\Filesystem\Filesystem */
    protected $fs;

    /** @var \Doctrine\Common\Annotations\AnnotationReader */
    protected $reader;

    protected $annotations = [ ];

    /**
     * Scanner constructor.
     *
     * @param \Laradic\AnnotationScanner\Factory            $addons
     * @param \Laradic\AnnotationScanner\Manifest           $manifest
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader
     * @param \Sebwite\Filesystem\Filesystem                $fs
     */
    public function __construct(AnnotationReader $reader, Filesystem $fs)
    {
        $this->fs       = new Filesystem();
        $this->reader   = new AnnotationReader();
    }

    public function addAnnotation($class)
    {
        $class = is_array($class) ? $class : func_get_args();
        foreach ( $class as $clas ) {
            $this->annotations[] = $clas;
        }
    }

    /**
     * @return array
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * Set the annotations value
     *
     * @param array $annotations
     *
     * @return Factory
     */
    public function setAnnotations($annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }



    public function registerAnnotation($filePath)
    {
        AnnotationRegistry::registerFile($filePath);
    }


    public function scanDirectory($path)
    {
        $files = [ ];
        foreach ( $this->createAnnotationScanner($this->annotations)->in($path) as $file )
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
