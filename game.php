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
		$this->initData();
		if($this->data['O'] == $player) {
			$this->symbol = 'O';
		} elseif($this->data['X'] == $player) {
			$this->symbol = 'X';
		} else {
			$this->symbol = self::EMPTY_SYMBOL;
		}
	}

	private function initData() {
		$transaction = $this->datastore->transaction();
		$entity = $transaction->lookup($this->key);
		if(!is_null($entity)) {
			if($entity['O'] != $this->player && is_null($entity['X'])) {
				echo $this->player, ' joined to the game.', PHP_EOL;
				$entity['X'] = $this->player;
				$transaction->update($entity);
				$transaction->commit();
			}
			$this->data = $entity;
		} else {
			$this->newGame();
		}
	}

	public function newGame() {
		echo $this->player, ' started new game.', PHP_EOL;
                $data = [
                        'O' => $this->player,
                        'X' => NULL,
                        'board' => array_fill(0, self::SIZE * self::SIZE, self::EMPTY_SYMBOL),
                        'started' => time(),
                ];
                $entity = $this->datastore->entity($this->key, $data);
		$this->datastore->upsert($entity);
		$this->data = $entity;
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

	public function move($x, $y) {
		if($this->isOver()) {
			throw new Exception("This game is over already.");
		}
		if($this->symbol == EMPTY_SYMBOL) {
			throw new Exception('@' . $this->player . ', you are not a player!');
		}
                if($this->player != $this->getNextPlayer()) {
                        throw new Exception('@' . $this->player . ', not your turn.');
                }
		$board = $this->data['board'];
		$pos = $x - 1 + ($y - 1) * self::SIZE; // zero base
		if($board[$pos] != self::EMPTY_SYMBOL) {
			throw new Exception("${x}, ${y} is already marked.");
		}
		$board[$pos] = $this->symbol;
		$this->data['board'] = $board;
		$this->autoFillIfLast();
		$this->datastore->upsert($this->data);
	}

	private function autoFillIfLast() {
		$board = $this->data['board'];
		$values = array_count_values($board);
		if($values[self::EMPTY_SYMBOL] != 1) {
			return;
		}
		$i = array_search(self::EMPTY_SYMBOL, $board);
		$board[$i] = $values['O'] > $values['X'] ? 'X' : 'O';
		$this->data['board'] = $board;
	}

	public function isOver() {
		return !is_null($this->getWinner()) || $this->isFull();
	}

	public function isFull() {
		$values = array_count_values($this->data['board']);
		return !array_key_exists(self::EMPTY_SYMBOL, $values);
	}

	public function getNextPlayer() {
		$values = array_count_values($this->data['board']);
		switch(count($values)) {
			case 1:
				return $this->data['O'];
			case 2:
				return $this->data['X'];
			default:
				return $values['O'] > $values['X'] ? $this->data['X'] : $this->data['O'];
		}
		
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
		for($i = 0; $i < count($positions) - self::SIZE + 1; $i++) {
			if(array_key_exists(serialize(array_slice($positions, $i, self::SIZE)), self::$winning_positions)) {
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
