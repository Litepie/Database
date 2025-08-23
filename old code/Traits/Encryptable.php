<?php


namespace Litepie\Database\Traits;

use Illuminate\Support\Facades\Crypt;

trait Encryptable
{
    /**
     * The attributes that should be encrypted.
     *
     * @var array
     */
    protected $encryptable = [];

    /**
     * Encrypt the attribute before saving.
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable) && !is_null($value)) {
            $value = Crypt::encrypt($value);
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * Decrypt the attribute when getting.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        if (in_array($key, $this->encryptable) && !is_null($value)) {
            return Crypt::decrypt($value);
        }
        return $value;
    }
}
