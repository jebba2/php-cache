<?php
/**
 * Class CacheDriverAbstract
 *
 * @filesource   CacheDriverAbstract.php
 * @created      25.05.2017
 * @package      chillerlan\SimpleCache
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\SimpleCache;

use chillerlan\Settings\SettingsContainerInterface;
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, NullLogger};
use Psr\SimpleCache\CacheInterface;
use DateInterval, DateTime, Traversable;

abstract class CacheDriverAbstract implements CacheInterface, LoggerAwareInterface{
	use LoggerAwareTrait;

	/**
	 * @var \chillerlan\SimpleCache\CacheOptions
	 */
	protected $options;

	/**
	 * CacheDriverAbstract constructor.
	 *
	 * @param \chillerlan\Settings\SettingsContainerInterface|null $options
	 */
	public function __construct(SettingsContainerInterface $options = null){
		$this->options = $options ?? new CacheOptions;
		$this->logger  = new NullLogger;
	}

	/** @inheritdoc */
	public function has($key):bool{
		return $this->get($key) !== null;
	}

	/** @inheritdoc */
	public function getMultiple($keys, $default = null):array{
		$data = [];

		foreach($this->getData($keys) as $key){
			$data[$key] = $this->get($key, $default);
		}

		return $data;
	}

	/** @inheritdoc */
	public function setMultiple($values, $ttl = null):bool{
		$return = [];

		foreach($this->getData($values) as $key => $value){
			$return[] = $this->set($key, $value, $ttl);
		}

		return $this->checkReturn($return);
	}

	/** @inheritdoc */
	public function deleteMultiple($keys):bool{
		$return = [];

		foreach($this->getData($keys) as $key){
			$return[] = $this->delete($key);
		}

		return $this->checkReturn($return);
	}

	/**
	 * @param string|mixed $key
	 *
	 * @return string
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function checkKey($key):string{

		if(!is_string($key) || empty($key)){
			$msg = 'invalid cache key: "'.$key.'"';
			$this->logger->error($msg);

			throw new InvalidArgumentException($msg);
		}

		return $key;
	}

	/**
	 * @param array $keys
	 *
	 * @return void
	 */
	protected function checkKeyArray(array $keys):void{

		foreach($keys as $key){
			$this->checkKey($key);
		}

	}

	/**
	 * @param mixed $data
	 *
	 * @return array
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function getData($data):array{

		if(is_array($data)){
			return $data;
		}
		elseif($data instanceof Traversable){
			return iterator_to_array($data); // @codeCoverageIgnore
		}

		$msg = 'invalid data';
		$this->logger->error($msg);

		throw new InvalidArgumentException($msg);
	}

	/**
	 * @param mixed $ttl
	 *
	 * @return int|null
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function getTTL($ttl):?int{

		if($ttl instanceof DateInterval){
			return (new DateTime)->add($ttl)->getTimeStamp() - time();
		}
		else if((is_int($ttl) && $ttl > 0) || $ttl === null){
			return $ttl;
		}

		$msg = 'invalid ttl';
		$this->logger->error($msg);

		throw new InvalidArgumentException($msg);
	}

	/**
	 * @param bool[] $booleans
	 *
	 * @return bool
	 */
	protected function checkReturn(array $booleans):bool{

		foreach($booleans as $bool){

			if(!(bool)$bool){
				return false; // @codeCoverageIgnore
			}

		}

		return true;
	}

}
