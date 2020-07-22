<?php

namespace Illuminate\Support\Facades {

    use Illuminate\Config\Repository;
    use Illuminate\Container\Container;
    use Illuminate\Hashing\HashManager;

    class Config {

        /**
         * Determine if the given configuration value exists.
         *
         * @param string $key
         * @return bool
         * @static
         */
        public static function has($key)
        {
            /** @var \Illuminate\Config\Repository $instance */
            return $instance->has($key);
        }

        /**
         * Get the specified configuration value.
         *
         * @param array|string $key
         * @param mixed $default
         * @return mixed
         * @static
         */
        public static function get($key, $default = null)
        {
            /** @var \Illuminate\Config\Repository $instance */
            $instance = new Repository();
            return $instance->get($key, $default);
        }

        /**
         * Get many configuration values.
         *
         * @param array $keys
         * @return array
         * @static
         */
        public static function getMany($keys)
        {
            /** @var \Illuminate\Config\Repository $instance */
            return $instance->getMany($keys);
        }

        /**
         * Set a given configuration value.
         *
         * @param array|string $key
         * @param mixed $value
         * @return void
         * @static
         */
        public static function set($key, $value = null)
        {
            /** @var \Illuminate\Config\Repository $instance */
            $instance = new Repository();
            $instance->set($key, $value);
        }

        /**
         * Prepend a value onto an array configuration value.
         *
         * @param string $key
         * @param mixed $value
         * @return void
         * @static
         */
        public static function prepend($key, $value)
        {
            /** @var \Illuminate\Config\Repository $instance */
            $instance->prepend($key, $value);
        }

        /**
         * Push a value onto an array configuration value.
         *
         * @param string $key
         * @param mixed $value
         * @return void
         * @static
         */
        public static function push($key, $value)
        {
            /** @var \Illuminate\Config\Repository $instance */
            $instance = new Repository();
            $instance->push($key, $value);
        }

        /**
         * Get all of the configuration items for the application.
         *
         * @return array
         * @static
         */
        public static function all()
        {
            /** @var \Illuminate\Config\Repository $instance */
            $instance = new Repository();
            return $instance->all();
        }

        /**
         * Determine if the given configuration option exists.
         *
         * @param string $key
         * @return bool
         * @static
         */
        public static function offsetExists($key)
        {
            /** @var \Illuminate\Config\Repository $instance */
            return $instance->offsetExists($key);
        }

        /**
         * Get a configuration option.
         *
         * @param string $key
         * @return mixed
         * @static
         */
        public static function offsetGet($key)
        {
            /** @var \Illuminate\Config\Repository $instance */
            return $instance->offsetGet($key);
        }

        /**
         * Set a configuration option.
         *
         * @param string $key
         * @param mixed $value
         * @return void
         * @static
         */
        public static function offsetSet($key, $value)
        {
            /** @var \Illuminate\Config\Repository $instance */
            $instance->offsetSet($key, $value);
        }

        /**
         * Unset a configuration option.
         *
         * @param string $key
         * @return void
         * @static
         */
        public static function offsetUnset($key)
        {
            /** @var \Illuminate\Config\Repository $instance */
            $instance->offsetUnset($key);
        }
    }
    /**
     *
     *
     * @see \Illuminate\Hashing\HashManager
     */
    class Hash {

        /**
         * Create an instance of the Bcrypt hash Driver.
         *
         * @return \Illuminate\Hashing\BcryptHasher
         * @static
         */
        public static function createBcryptDriver()
        {
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->createBcryptDriver();
        }

        /**
         * Create an instance of the Argon2i hash Driver.
         *
         * @return \Illuminate\Hashing\ArgonHasher
         * @static
         */
        public static function createArgonDriver()
        {
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->createArgonDriver();
        }

        /**
         * Create an instance of the Argon2id hash Driver.
         *
         * @return \Illuminate\Hashing\Argon2IdHasher
         * @static
         */
        public static function createArgon2idDriver()
        {
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->createArgon2idDriver();
        }

        /**
         * Get information about the given hashed value.
         *
         * @param string $hashedValue
         * @return array
         * @static
         */
        public static function info($hashedValue)
        {
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->info($hashedValue);
        }

        /**
         * Hash the given value.
         *
         * @param string $value
         * @param array $options
         * @return string
         * @static
         */
        public static function make($value, $options = array())
        {
            /** @var \Illuminate\Hashing\HashManager $instance */
            $container = Container::getInstance();
            $instance = new HashManager($container);
            return $instance->make($value, $options);
        }

        /**
         * Check the given plain value against a hash.
         *
         * @param string $value
         * @param string $hashedValue
         * @param array $options
         * @return bool
         * @static
         */
        public static function check($value, $hashedValue, $options = array())
        {
            /** @var \Illuminate\Hashing\HashManager $instance */
            $container = Container::getInstance();
            $instance = new HashManager($container);
            return $instance->check($value, $hashedValue, $options);
        }

        /**
         * Check if the given hash has been hashed using the given options.
         *
         * @param string $hashedValue
         * @param array $options
         * @return bool
         * @static
         */
        public static function needsRehash($hashedValue, $options = array())
        {
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->needsRehash($hashedValue, $options);
        }

        /**
         * Get the default driver name.
         *
         * @return string
         * @static
         */
        public static function getDefaultDriver()
        {
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->getDefaultDriver();
        }

        /**
         * Get a driver instance.
         *
         * @param string $driver
         * @return mixed
         * @throws \InvalidArgumentException
         * @static
         */
        public static function driver($driver = null)
        {
            //Method inherited from \Illuminate\Support\Manager
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->driver($driver);
        }

        /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Hashing\HashManager
         * @static
         */
        public static function extend($driver, $callback)
        {
            //Method inherited from \Illuminate\Support\Manager
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->extend($driver, $callback);
        }

        /**
         * Get all of the created "drivers".
         *
         * @return array
         * @static
         */
        public static function getDrivers()
        {
            //Method inherited from \Illuminate\Support\Manager
            /** @var \Illuminate\Hashing\HashManager $instance */
            return $instance->getDrivers();
        }

    }
}

namespace  {
    class Config extends \Illuminate\Support\Facades\Config {}
    class Hash extends \Illuminate\Support\Facades\Hash {}
}