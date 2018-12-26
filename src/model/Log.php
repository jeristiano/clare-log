<?php

namespace Jeristiano\AtlasLog\model;

use Exception;

class Log
{

    /**
     * 获取当前的日志文件集合
     * @return array
     */
    public function files($path = '',$ingnore=true)
    {
        $path = $path ?: $this->getLogStoragePath();

        $path .= substr($path, -1) != DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '';
        $files = glob($path . '*');
        if($ingnore){
            $files = $this->excludeDirectory($files);
        }
        if (false === $files) {
            throw new Exception('无法获取日志目录文件');
        }
        $rows = [];
        foreach ($files as $fileOrDirectory) {
            if (is_dir($fileOrDirectory)) {
                $rows = array_merge($rows, $this->files($fileOrDirectory,false));
            } else {
                $rows[] = [
                    'real' => base64_encode($fileOrDirectory),
                    'name' => self::removeRootPathPrefix($fileOrDirectory),
                ];
            }
        }
        return $rows;
    }

    private function excludeDirectory(array $files)
    {
        $files_arr=[];
        $time = date('Ym', time());
        foreach ($files as $file) {
            $file_path = explode('/', $file);
            $exclude=end($file_path);
            if ($time == $exclude) {
                $files_arr[]=$file;
                break;
            }
        }
        return $files_arr;
    }

    public function paginate($file, $page = 1, $pageSize = 20)
    {
        $file = base64_decode($file);
        $file = $this->getRealFilePath($file);
        $fileHandle = fopen($file, "r");
        if (false === $fileHandle) {
            throw new Exception('can not open log file.');
        }
        $size = 0;
        $exploder = str_pad('', 63, '-');
        $start = ($page - 1) * $pageSize;
        $end = $start + $pageSize;
        $rows = [];
        $block = [];
        while (!feof($fileHandle)) {
            $line = trim(fgets($fileHandle));
            if ($line == $exploder) {
                $size++;
                $block = [];
            } else {
                $block[] = $line;
            }
            if ($size >= $start && $size < $end) {
                $rows[$size] = $block;
            }
        }
        fclose($fileHandle);

        return [
            'meta' => [
                'total' => $size,
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_page' => $size % $pageSize == 0 ? $size / $pageSize : (int)($size / $pageSize) + 1,
            ],
            'data' => $rows,
        ];
    }

    /**
     * 删除日志文件
     * @param $file 日志文件名
     * @return void
     */
    public function destroy($file)
    {
        $file = $this->getRealFilePath($file);
        unlink($file);
    }

    protected function getRealFilePath($file)
    {
        $filePath = $file;
        if (!file_exists($filePath)) {
            throw new Exception('file not found.', 404);
        }
        $filePath = realpath($filePath);
        if (!preg_match('/' . str_replace(DIRECTORY_SEPARATOR, '#', self::getLogStoragePath()) . '/', str_replace(DIRECTORY_SEPARATOR, '#', $filePath))) {
            throw new Exception('文件路径不合法');
        }
        return $filePath;
    }

    public static function getLogStoragePath()
    {
        static $path;
        if (!$path) {
            $config = config('log');
            $path = $config['path'];
            if ($path) {
                substr($path, -1) != DIRECTORY_SEPARATOR && $path .= DIRECTORY_SEPARATOR;
            } else {
                $path = RUNTIME_PATH . 'log' . DIRECTORY_SEPARATOR;
            }
        }
        return $path;
    }

    public static function removeRootPathPrefix($path)
    {
        return str_replace(self::getLogStoragePath(), '', $path);
    }

}