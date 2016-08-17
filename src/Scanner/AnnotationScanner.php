<?php
/**
 * Copyright (c) 2012-2013 Maximilian Reichel <info@phramz.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Codex\Addons\Scanner;

use Doctrine\Common\Annotations\Reader;
use Laradic\AnnotationScanner\Exception\AnnotationScannerException;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Scanner
 * @package Codex\Core\Addons\Scanner
 */
class AnnotationScanner implements \IteratorAggregate
{
    private $realpath = false;

    private $depth = 5;

    /**
     * @var Reader
     */
    private $reader = null;

    private $path = array();
    private $exclude = array();
    private $annotations = array();

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function realpath($enabled = true)
    {
        $this->realpath = $enabled;
        return $this;
    }

    public function in($path)
    {
        $this->path[] = $path;

        return $this;
    }

    public function exclude($path)
    {
        $this->exclude[] = $path;

        return $this;
    }

    public function annotations(array $annotations)
    {
        $this->annotations = $annotations;

        return $this;
    }

    public function depth($depth)
    {
        $this->depth = $depth;
        return $this;
    }

    public function getIterator()
    {
        $result = array();

        $finder = new SymfonyFinder();
        $finder->files()
            ->followLinks()
            ->name('*.php')
            ->in($this->path)
            ->depth('<=' . $this->depth)
            ->exclude($this->exclude);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            try {
                $fileInspector = new FileInspector($file->getPathname());
                $classInspector = $fileInspector->getClassInspector($this->reader);

                foreach ($this->annotations as $annotation) {
                    if ($classInspector->containsClassAnnotation($annotation)
                        || $classInspector->containsMethodAnnotation($annotation)
                        || $classInspector->containsPropertyAnnotation($annotation)) {

                        $result[] = new ClassFileInfo($file, $classInspector);
                        continue;
                    }
                }

            } catch (AnnotationScannerException $ex) {
                continue;
            }
        }

        return new \ArrayIterator($result);
    }
}
