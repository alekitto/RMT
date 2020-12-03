<?php declare(strict_types=1);

namespace Liip\RMT\Output;

use Symfony\Component\Console\Output\StreamOutput;

if ((new \ReflectionMethod(StreamOutput::class, 'doWrite'))->getParameters()[0]->hasType()) {
    trait DoWriteTrait
    {
        public function doWrite(string $message, bool $newline): void
        {
            // In case the $message is multi lines
            $message = str_replace(PHP_EOL, PHP_EOL.$this->getIndentPadding(), $message);

            if ($this->positionIsALineStart) {
                $message = $this->getIndentPadding().$message;
            }

            $this->positionIsALineStart = $newline;
            parent::doWrite($message, $newline);
        }
    }
} else {
    trait DoWriteTrait
    {
        public function doWrite($message, $newline)
        {
            // In case the $message is multi lines
            $message = str_replace(PHP_EOL, PHP_EOL.$this->getIndentPadding(), $message);

            if ($this->positionIsALineStart) {
                $message = $this->getIndentPadding().$message;
            }

            $this->positionIsALineStart = $newline;
            parent::doWrite($message, $newline);
        }
    }
}
