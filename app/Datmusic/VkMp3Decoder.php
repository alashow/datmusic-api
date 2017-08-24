<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace app\Datmusic;

class VkMp3Decoder
{
    private $encoded;

    /**
     * Node.js binary path.
     *
     * @var string
     */
    private $nodejs;
    /**
     * decode.js full file path.
     *
     * @var string
     */
    private $js;

    public function __construct($encoded)
    {
        $this->encoded = $encoded;
        $this->nodejs = config('app.paths.nodejs');
        $this->js = config('app.paths.decode-js');
    }

    public function decodeMp3Url()
    {
        if (empty($this->encoded)) {
            logger()->log('Decoder.Empty');

            return '';
        }

        // execute decode.js with nodejs
        return exec("{$this->nodejs} {$this->js} {$this->encoded}");
    }
}
