<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Datastore\DatastoreClient;
use Google\Cloud\Datastore\Key;

class Game {
	const EMPTY_SYMBOL = ' ';
	const SIZE = 3;
	static $winning_positions;

	public function __construct($id, $player) {
		$this->datastore = new DatastoreClient([
			'projectId' => 'tic-tac-toe-168804'
		]);
		$this->key = $this->datastore->key('Game', $id, [
			'identifierType' => Key::TYPE_NAME
		]);
		$this->player = $player;
		$this->data = $this->getData();
		if($this->data['O'] == $player) {
			$this->symbol = 'O';
		} elseif($this->data['X'] == $player) {
			$this->symbol = 'X';
		} else {
			$this->symbol = self::EMPTY_SYMBOL;
		}
	}

	private function getData() {
		$transaction = $this->datastore->transaction();
		$entity = $transaction->lookup($this->key);
		if(!is_null($entity)) {
			if($entity['O'] != $this->player && is_null($entity['X'])) {
				$entity['X'] = $this->player;
				$transaction->update($entity);
			}
		} else {
			$data = [
				'O' => $this->player,
				'X' => NULL,
				'board' => array_fill(0, self::SIZE * self::SIZE, self::EMPTY_SYMBOL),
				'started' => time(),
			];
			$entity = $this->datastore->entity($this->key, $data);
			$transaction->insert($entity);
		}
		$transaction->commit();
		return $entity;
	}

	public function get() {
		return $this->data['board'];
	}

	public function printBoard() {
		foreach($this->data['board'] as $i => $symbol) {
			echo "| ${symbol} ";
			$mod = ($i + 1) % self::SIZE;
			if($mod == 0) {
				echo '|', PHP_EOL;
				if($i + 1 < self::SIZE * self::SIZE) {
					echo '|---+---+---|', PHP_EOL;
				}
			} 
		}
	}

	public function mark($x, $y) {
		if($this->isFull()) {
			throw new Exception("This game is over already.");
		}
		if($this->symbol == EMPTY_SYMBOL) {
			throw new Exception($this->player . ', you are not a player!');
		}
		$pos = $x + $y * self::SIZE;
		if($this->data['board'][$pos] != EMPTY_SYMBOL) {
			throw new Exception("${x}, ${y} is already marked.");
		}
		$this->data['board'][$pos] = $this->symbol;
		$this->autoFillIfLast();
		$entity = $this->datastore->entity($this->key, $this->data);
		$this->datastore->upsert($entity);
	}

	private function autoFillIfLast() {
		$values = array_count_values($this->data['board']);
		if($values[self::EMPTY_SYMBOL] != 1) {
			return;
		}
		$i = array_search(self::EMPTY_SYMBOL, $this->data['board']);
		$this->data['board'][$i] = $values['O'] > $values['X'] ? 'X' : 'O';
	}

	public function isOver() {
		return !is_null($this->getWinner()) || $this->isFull();
	}

	public function isFull() {
		$values = array_count_values($this->data['board']);
		return count($values) == 2;
	}
	
	public function getWinner() {
		if($this->isWinner('O')) {
			return 'O';
		} 
		if($this->isWinner('X')) {
			return 'X';
		}
		return NULL;
	}

	public function isWinner($symbol) {
                $positions = array_values(array_keys($this->data['board'], $symbol));
		if(count($positions) < self::SIZE) {
			return FALSE;
		}
		for($i = 0; $i < count($positions) - self::SIZE - 1; $i++) {
			if(array_key_exists(serialize(array_slice($positions, $i, self::SIZE), self::$winning_positions))) {
				return TRUE;
			}
		}
		return FALSE;
	}
}
Game::$winning_positions = [
        serialize([0, 1, 2]) => NULL, // top row
        serialize([3, 4, 5]) => NULL, // middle row
        serialize([6, 7, 8]) => NULL, // bottom row
        serialize([0, 3, 6]) => NULL, // left col
        serialize([1, 4, 7]) => NULL, // middle col
        serialize([2, 5, 8]) => NULL, // right col
        serialize([0, 4, 8]) => NULL, // diagonal
        serialize([2, 4, 6]) => NULL, // diagonal
];
