<?php

namespace Database\Seeders;

use Defuse\Crypto\Key;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use phpseclib\Crypt\RSA as Crypt_RSA;
use Bytesfield\KeyManager\Facades\KeyManager;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [];
        $keys = [];
        for ($i = 0; $i < 10; $i++) {
            $keys[] = Key::createNewRandomKey()->saveToAsciiSafeString();
            $users[] = [
                'username' => fake()->unique()->userName(),
                'password' => $this->EncryptRC4('123123123', $keys[$i]),
                'name' => $this->EncryptRC4(fake()->name(), $keys[$i]),
                'id_number' => $this->EncryptRC4(fake()->randomNumber(), $keys[$i]),
                'student_id_number' => $this->EncryptRC4(fake()->randomNumber(), $keys[$i]),
                'date_of_birth' => $this->EncryptRC4(fake()->date(), $keys[$i]),
                'address' => $this->EncryptRC4(fake()->address(), $keys[$i]),
                'phone' => $this->EncryptRC4(fake()->phoneNumber(), $keys[$i]),
                'university' => $this->EncryptRC4(fake()->company(), $keys[$i]),
                'major' => $this->EncryptRC4(fake()->jobTitle(), $keys[$i]),
                'resume_video' => null,
                'email' => $this->EncryptRC4(fake()->unique()->safeEmail(), $keys[$i]),
                'email_verified_at' => null,
            ];
        }

        for ($i = 0; $i < 10; $i++) {
            $user = \App\Models\User::create($users[$i]);
            $key = $keys[$i];
            $userkey = \App\Models\UserKey::create([
                'user_id' => $user->id,
                'key' => $key,
            ]);
            KeyManager::createClient($user->username, 'user', 'active');
        }
    }

    public function EncryptAES($plaintext, $key){
        $cipher = 'aes-256-cbc';
        $iv = substr($key, 0, 16);
        $encrypted = base64_encode(openssl_encrypt($plaintext, $cipher, $key, 0, $iv));
        return $encrypted;
    }

    public function EncryptRC4($plaintext, $key){
        $cipher = 'rc4';
        $encrypted = base64_encode(openssl_encrypt($plaintext, $cipher, $key));
        return $encrypted;
    }

    public function EncryptDES($plaintext, $key){
        $cipher = 'des-ecb';
        $encrypted = base64_encode(openssl_encrypt($plaintext, $cipher, $key, $options = 0));
        return $encrypted;
    }
}
