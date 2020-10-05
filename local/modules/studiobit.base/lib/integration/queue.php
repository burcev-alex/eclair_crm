<?
namespace Studiobit\Base\Integration;

use Studiobit\Base;
use Studiobit\Base\IblockOrm;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock as Iblock;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPConnection;

Loc::loadMessages(__FILE__);

/**
 * Работа c очередями
 * Class Queue
 */
class Queue {
    private $host = "localhost";
    private $port = 5672;
    private $login = "admin";
    private $password = "admin";

	protected $connection;
	protected $channel;

	protected $exchange = "router";
	protected $consumerTag = "consumer";

	public function __construct($exchange = "", $consumerTag = "")
	{
		if(strlen($exchange) > 0) {
			$this->exchange = $exchange;
		}
		if(strlen($consumerTag) > 0) {
			$this->consumerTag = $consumerTag;
		}

		$this->connection = new AMQPConnection($this->host, $this->port, $this->login, $this->password);
		$this->channel = $this->connection->channel();
	}

	/**
	 * Добавить сообщение в очередь
	 * @param $queue - название очереди
	 * @param $message - сообщение
	 */
	public function AddMessage($queue, $message){
		// добавить очередь
		$this->channel->queue_declare($queue, false, false, false, false);

		#$this->channel->exchange_declare($this->exchange, 'direct', false, true, false);

		#$this->channel->queue_bind($queue, $this->exchange);

		$msg = new AMQPMessage($message);
		$this->channel->basic_publish($msg, '', $queue);
	}

	/**
	 * Вытянуть сообщение из очереди
	 * @param $queue
	 * @param string $nameFuncCallback
	 * @param string $count
	 */
	public function GetMessage($queue, $nameFuncCallback = '', $count = ""){
		if(!is_array($nameFuncCallback)) {
			if (strlen($nameFuncCallback) == 0) {
				$nameFuncCallback = array($this, 'process_message');
			}
		}
		$this->channel->basic_consume(
			$queue,
			'', // $this->consumerTag
			false,
			true,
			false,
			false,
			$nameFuncCallback
		);

		if($count == "only") {
			while (count($this->channel->callbacks)) {
				// add here other sockets that you need to attend
				$read = array($this->connection->getSocket());
				$write = null;
				$except = null;
				$changeStreamsCount = stream_select($read, $write, $except, 60);

				if (IntVal($changeStreamsCount) == 0) {
					break;
				} else if ($changeStreamsCount > 0) {
					$this->channel->wait();
				}
			}
		}
		else {
			while (count($this->channel->callbacks)) {
				$this->channel->wait();
			}
		}
	}

	public function clear()
	{
		$this->channel->close();
		$this->connection->close();
	}

	public function process_message($message){
		$msq = json_decode($message->body, true);
		AddMessage2Log($msq);
	}
}
?>