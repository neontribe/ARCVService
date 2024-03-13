<?php


namespace App\Wrappers;

use Illuminate\Support\Str;

/**
 * This stream wrapper allows the creation of a resource that will encrypt all data forwarded to another resource, such
 * as a file or socket.
 *
 * Stream wrappers are fairly poorly documented in the PHP manual. They can be thought of as abstract files, which may
 * or may not wrap another resource.
 *
 * https://www.php.net/manual/en/class.streamwrapper.php - for the "class" this one extends
 * https://www.php.net/manual/en/stream.streamwrapper.example-1.php - for an example of another
 * https://www.php.net/manual/en/stream.streamwrapper.example-1.php#99283 - for a more straight forward example of another
 *
 * The encryption used is provided by `libsodium`, a popular and robust library that can have its bindings compiled into
 * PHP itself (as of version 7.2). The particular primitive is secret stream, which allows messages of known size from a
 * larger source to be decrypted individually, with guarantees of order and completeness. This is important if we do not
 * want the hold the entirety of a file in memory at any given time.
 *
 * Documentation is similarly poor for secret streams in PHP but examples are common for other languages. They can be
 * thought of as encryption and decryption with state.
 *
 * https://libsodium.gitbook.io/doc/secret-key_cryptography/secretstream - description of the primitive and C examples
 * https://github.com/php/php-src/blob/PHP-7.2.20/ext/sodium/libsodium.c#L3518 - the implementation of the methods for PHP
 * https://github.com/php/php-src/blob/PHP-7.2.20/ext/sodium/tests/crypto_secretstream.phpt - expected outputs of PHP implementation
 *
 * NOTE: the class must be registered with @see stream_register_wrapper() before use as a protocol (`ssw://*`).
 *
 * @package App\Wrappers
 */
class SecretStreamWrapper
{
    // The size of each encrypted chunk of a file.
    public const MESSAGE_SIZE = 1024 * 1024; // 1 mebibyte

    // The stream context that the resource has been opened with.
    public $context;

    // The wrapped resource.
    private $wrapped;

    // The secret stream that we are wrapping with.
    private $stream;

    // We keep at least one message in a buffer so that we can output and mark the final message as final on closing.
    private $buffer;

    /**
     * This would be triggered in scenarios such as `fopen("ssw://*")`. It establishes the resource.
     *
     * We setup the resource we're wrapping and the cryptography we're going to rely on, deriving our key from
     * self::getKey().
     *
     * @param $path string the full path provided to @see fopen() or similar
     * @param $mode string only "r" is supported
     * @param $options int some options encoded as bits
     *
     * @return bool whether opening was successful
     */
    public function stream_open($path, $mode, $options) {
        // Check that we are in write mode.
        if ($mode !== "w") {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('Secret stream wrapper may only be opened in "w" mode');
            }
            return false;
        }

        // Open wrapped resource. TODO : It would be nice if our parsing was more robust.
        $parts = explode("://", $path);
        if (count($parts) < 2) {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('Unable to parse wrapper-received URL');
            }
            return false;
        }
        $wrappedPath = implode("://", array_slice($parts, 1));
        if (!($this->wrapped = fopen($wrappedPath, $mode))) {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('Wrapped path could not be opened');
            }
            return false;
        }

        // Initialise secret stream.
        list($this->stream, $header) = sodium_crypto_secretstream_xchacha20poly1305_init_push(self::getKey());

        // Write out initial header.
        if (!fwrite($this->wrapped, $header)) {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('IO error when writing header');
            }
            return false;
        }

        return true;
    }

    /**
     * Handles writes to the resource, which we will encrypt. e.g. `fwrite()` would call this.
     *
     * @param $data the plaintext to write
     * @return int how many bytes of input we used, else 0 if everything went wrong
     */
    public function stream_write($data) {
        // Append data to our internal buffer.
        $this->buffer .=  $data;

        // Write out as many full-sized messages as we can without fully depleting the buffer.
        if ($this->flushBuffer()) {
            // We wrote everything provided to us.
            return strlen($data);
        }

        // We failed to write.
        return 0;
    }

    /**
     * Flush out our final data and close all resources. e.g. `fclose()` would call this.
     */
    public function stream_close() {
        // Write out data as encrypted messages until we have only the final message's data in the buffer.
        $this->flushBuffer();

        // Flush out the final message, tagged as such.
        if (strlen($this->buffer) > 0) {
            $encrypted = sodium_crypto_secretstream_xchacha20poly1305_push(
                $this->stream,
                $this->buffer,
                '', // This is an inactive placeholder.
                SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
            );
            fwrite($this->wrapped, $encrypted); // TODO : Can an error here be caught somehow?
        }

        // Close our wrapped resource.
        fclose($this->wrapped);
    }

    /**
     * This is used to flush out our internal buffer, clearing everything until we only have enough left to send a final
     * message, which we have to tag (and so send in stream_close()).
     *
     * @return bool whether all writes succeeded
     */
    private function flushBuffer() {
        // While we can send a full-sized message and still have data left for the final message...
        while (strlen($this->buffer) > self::MESSAGE_SIZE) {
            // Write out a message's worth of data from the start of the buffer.
            $encrypted = sodium_crypto_secretstream_xchacha20poly1305_push(
                $this->stream,
                substr($this->buffer, 0, self::MESSAGE_SIZE)
            );
            $this->buffer = substr($this->buffer, self::MESSAGE_SIZE);
            if (!fwrite($this->wrapped, $encrypted)) return false;
        }
        return true;
    }

    /**
     * Get the key that we will use for encryption, as defined in the config (and so probably by the environment
     * variable APP_KEY.)
     *
     * @return string the key itself to use
     */
    public static function getKey() {
        $key = config('app.key');

        // Sometimes, the key is encoded in base64. Decrypt it if this is the case.
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        // Check that the key is the write length.
        // TODO : Can we guarantee that app.key will be configured as 32 random bytes?
        if (strlen($key) !== SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES) {
            throw new \RuntimeException("Randomly generated `app.key` wrong length / ill-defined");
        }

        return $key;
    }
}