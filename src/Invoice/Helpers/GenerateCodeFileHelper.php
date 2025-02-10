<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\Html\Html;

/**
 * CodeFile represents a code file to be generated.
 *
 * @property string $relativePath The code file path relative to the application base path. This property is
 * read-only.
 * @property string $type The code file extension (e.g. php, txt). This property is read-only.
 */
class GenerateCodeFileHelper
{
    /**
     * The code file is new.
     */
    public const string OP_CREATE = 'create';
    /**
     * The code file already exists, and the new one may need to overwrite it.
     */
    public const string OP_OVERWRITE = 'overwrite';
    /**
     * The new code file and the existing one are identical.
     */
    public const string OP_SKIP = 'skip';

    /**
     * @var string an ID that uniquely identifies this code file.
     */
    public $id;
    /**
     * @var string the file path that the new code should be saved to.
     */
    public $basepath;
    /**
     * @var string the file path that the new code should be saved to.
     */
    public $path;
    /**
     * @var string the newly generated code content
     */
    public $content;
    /**
     * @var string the operation to be performed. This can be [[OP_CREATE]], [[OP_OVERWRITE]] or [[OP_SKIP]].
     */
    public $operation;

    /**
     * Constructor.
     * @param string $path the file path that the new code should be saved to.
     * @param string $content the newly generated code content.
     */
    public function __construct($path, $content)
    {
        $this->path = strtr($path, '/\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);

        // $this->basepath used in function relativepath
        $this->basepath = dirname(__DIR__, 3);

        $this->content = $content;
        /**
         *  @see GeneratorController function build_and_save
         *  The MD5 hash algorithm was developed in 1991 and released in 1992.
         *  Only a year later, researchers were already finding flaws!
         *  However, it continued to be used and adopted by developers around the world.
         *  In 2005, it was officially deemed unsuitable, yet, in 2019, it was estimated
         *  that 25% of content management systems still use MD5!
         *  Recommendation: use password_hash instead ... Courtesy of Snyk
         * @see https://www.php.net/manual/en/function.md5.php
         */
        $this->id = password_hash($this->path, PASSWORD_DEFAULT);
        if (is_file($path)) {
            $this->operation = file_get_contents($path) === $content ? self::OP_SKIP : self::OP_OVERWRITE;
        } else {
            $this->operation = self::OP_CREATE;
        }
    }

    /**
     * Saves the code into the file specified by [[path]].
     *
     * @return string|true the error occurred while saving the code file, or true if no error.
     */
    public function save(): bool|string
    {
        if ($this->operation === self::OP_CREATE) {
            $dir = dirname($this->path);
            if (!is_dir($dir)) {
                $mask = @umask(0);
                $result = @mkdir($dir, 0777, true);
                @umask($mask);
                if (!$result) {
                    return "Unable to create the directory '$dir'.";
                }
            }
        }
        if (@file_put_contents($this->path, $this->content) === false) {
            return "Unable to write the file '{$this->path}'.";
        }
        return true;
    }

    /**
     * @return string the code file path relative to the application base path.
     */
    public function getRelativePath()
    {
        if (strpos($this->path, $this->basepath) === 0) {
            return substr($this->path, strlen($this->basepath) + 1);
        }
        return $this->path;
    }

    /**
     * @return string the code file extension (e.g. php, txt)
     */
    public function getType()
    {
        if (($pos = strrpos($this->path, '.')) !== false) {
            return substr($this->path, $pos + 1);
        }

        return 'unknown';
    }

    /**
     * Returns preview or false if it cannot be rendered
     *
     * @return false|string
     */
    public function preview(): string|false
    {
        if (($pos = strrpos($this->path, '.')) !== false) {
            $type = substr($this->path, $pos + 1);
        } else {
            $type = 'unknown';
        }

        if ($type === 'php') {
            return highlight_string($this->content, true);
        }
        if (!in_array($type, ['jpg', 'gif', 'png', 'exe'])) {
            return nl2br(Html::encode($this->content));
        }

        return false;
    }
}
