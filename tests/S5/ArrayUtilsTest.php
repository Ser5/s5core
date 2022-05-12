<?
namespace S5;


class ArrayUtilsTest extends \S5\TestCase {
	public function testGetAllCombinations_2_2_2 () {
		$lists = [
			['news', 'articles'],
			[10, 20],
			['vote', 'download'],
		];
		$got = ArrayUtils::getAllCombinations($lists);
		$expected = [
			['news',     10, 'vote'],
			['news',     10, 'download'],
			['news',     20, 'vote'],
			['news',     20, 'download'],
			['articles', 10, 'vote'],
			['articles', 10, 'download'],
			['articles', 20, 'vote'],
			['articles', 20, 'download'],
		];
		$this->assertEquals($expected, $got);
	}



	public function testGetAllCombinations_2_2_3 () {
		$lists = [
			['news', 'articles'],
			[10, 20],
			['vote', 'download', 'view'],
		];
		$got = ArrayUtils::getAllCombinations($lists);
		$expected = [
			['news',     10, 'vote'],
			['news',     10, 'download'],
			['news',     10, 'view'],
			['news',     20, 'vote'],
			['news',     20, 'download'],
			['news',     20, 'view'],
			['articles', 10, 'vote'],
			['articles', 10, 'download'],
			['articles', 10, 'view'],
			['articles', 20, 'vote'],
			['articles', 20, 'download'],
			['articles', 20, 'view'],
		];
		$this->assertEquals($expected, $got);
	}



	public function testGetAllCombinations_3_2_2 () {
		$lists = [
			['news', 'articles', 'photos'],
			[10, 20],
			['vote', 'download'],
		];
		$got = ArrayUtils::getAllCombinations($lists);
		$expected = [
			['news',     10, 'vote'],
			['news',     10, 'download'],
			['news',     20, 'vote'],
			['news',     20, 'download'],
			['articles', 10, 'vote'],
			['articles', 10, 'download'],
			['articles', 20, 'vote'],
			['articles', 20, 'download'],
			['photos',   10, 'vote'],
			['photos',   10, 'download'],
			['photos',   20, 'vote'],
			['photos',   20, 'download'],
		];
		$this->assertEquals($expected, $got);
	}



	public function testGetAllCombinations_1_1_1 () {
		$lists = [
			['news'],
			[10],
			['vote'],
		];
		$got = ArrayUtils::getAllCombinations($lists);
		$expected = [
			['news', 10, 'vote'],
		];
		$this->assertEquals($expected, $got);
	}



	public function testGetAllCombinations_EmptyList () {
		$lists    = [];
		$got      = ArrayUtils::getAllCombinations($lists);
		$expected = [];
		$this->assertEquals($expected, $got);
	}



	public function testGetAllCombinations_EmptySubarrays () {
		 $this->expectException(\InvalidArgumentException::class);

		ArrayUtils::getAllCombinations([
			['news', 'articles'],
			[],
			['vote', 'download'],
		]);
	}



	public function testGetAllCombinations_SubNotArrays () {
		$this->expectException(\InvalidArgumentException::class);

		ArrayUtils::getAllCombinations([
			['news', 'articles'],
			'Not-an-array',
			['vote', 'download'],
		]);
	}



	private $_sourceList = [
		['id'=>1,  'parent_id'=>0,  'title'=>'News'],
		['id'=>2,  'parent_id'=>0,  'title'=>'Articles'],
		['id'=>3,  'parent_id'=>1,  'title'=>'N1'],
		['id'=>4,  'parent_id'=>1,  'title'=>'N2'],
		['id'=>5,  'parent_id'=>3,  'title'=>'n1c1'],
		['id'=>6,  'parent_id'=>3,  'title'=>'n1c2'],
		['id'=>7,  'parent_id'=>4,  'title'=>'n2c1'],
		['id'=>8,  'parent_id'=>4,  'title'=>'n2c2'],
		['id'=>9,  'parent_id'=>2,  'title'=>'A1'],
		['id'=>10, 'parent_id'=>2,  'title'=>'A2'],
		['id'=>11, 'parent_id'=>9,  'title'=>'a1c1'],
		['id'=>12, 'parent_id'=>9,  'title'=>'a1c2'],
		['id'=>13, 'parent_id'=>10, 'title'=>'a2c1'],
		['id'=>14, 'parent_id'=>10, 'title'=>'a2c2'],
	];
	private $_expectedTree = array(
		'subtree' => [
			1 => [
				'id'        => 1,
				'parent_id' => 0,
				'title'     => 'News',
				'subtree' => [
					3 => [
						'id'        => 3,
						'parent_id' => 1,
						'title'     => 'N1',
						'subtree' => [
							5 => [
								'id'        => 5,
								'parent_id' => 3,
								'title'     => 'n1c1',
							],
							6 => [
								'id'        => 6,
								'parent_id' => 3,
								'title'     => 'n1c2',
							],
						],
					],
					4 => [
						'id'        => 4,
						'parent_id' => 1,
						'title'     => 'N2',
						'subtree' => [
							7 => [
								'id'        => 7,
								'parent_id' => 4,
								'title'     => 'n2c1',
							],
							8 => [
								'id'        => 8,
								'parent_id' => 4,
								'title'     => 'n2c2',
							],
						],
					],
				],
			],
			2 => [
				'id'        => 2,
				'parent_id' => 0,
				'title'     => 'Articles',
				'subtree' => [
					9 => [
						'id'        => 9,
						'parent_id' => 2,
						'title'     => 'A1',
						'subtree' => [
							11 => [
								'id'        => 11,
								'parent_id' => 9,
								'title'     => 'a1c1',
							],
							12 => [
								'id'        => 12,
								'parent_id' => 9,
								'title'     => 'a1c2',
							],
						],
					],
					10 => [
						'id'        => 10,
						'parent_id' => 2,
						'title'     => 'A2',
						'subtree' => [
							13 => [
								'id'        => 13,
								'parent_id' => 10,
								'title'     => 'a2c1',
							],
							14 => [
								'id'        => 14,
								'parent_id' => 10,
								'title'     => 'a2c2',
							],
						],
					],
				],
			],
		],
	);

	public function testGetTreeFromList () {
		$gotTree = ArrayUtils::getTreeFromList([
			'root_id'          => 0,
			'source'           => $this->_sourceList,
			'reader'           => false,
			'id_key_name'      => 'id',
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		]);
		$this->assertEquals($this->_expectedTree, $gotTree);
		//print_r($gotTree);
	}



	public function testGetTreeFromList_defaults () {
		$gotTree = ArrayUtils::getTreeFromList([
			'source'           => $this->_sourceList,
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		]);
		$this->assertEquals($this->_expectedTree, $gotTree);
	}



	public function testGetTreeFromList_reader () {
		$source = new \ArrayIterator($this->_sourceList);
		$reader = function ($source) {
			if ($source->valid()) {
				$value = $source->current();
				$source->next();
			} else {
				$value = false;
			}
			return $value;
		};
		$gotTree = ArrayUtils::getTreeFromList([
			'root_id'          => 0,
			'source'           => $source,
			'reader'           => $reader,
			'id_key_name'      => 'id',
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		]);
		$this->assertEquals($this->_expectedTree, $gotTree);
	}



	public function testGetTreeFromList_implicitRoot () {
		$source = [
			['id'=>1,  'parent_id'=>0,  'title'=>'News'],
		];
		$expectedTree = [
			'subtree' => array(
				1 => array(
					'id' => 1,
					'parent_id' => 0,
					'title' => 'News',
				),
			),
		];
		$gotTree = ArrayUtils::getTreeFromList([
			'root_id'          => 0,
			'source'           => $source,
			'reader'           => false,
			'id_key_name'      => 'id',
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		]);
		$this->assertEquals($expectedTree, $gotTree);
	}



	public function testGetTreeFromList_explicitRoot () {
		$source = [
			['id'=>1,  'parent_id'=>0,  'title'=>'Root'],
			['id'=>2,  'parent_id'=>1,  'title'=>'News'],
		];
		$expectedTree = [
			'id' => 1,
			'parent_id' => 0,
			'title' => 'Root',
			'subtree' => [
				2 => [
					'id' => 2,
					'parent_id' => 1,
					'title' => 'News',
				],
			],
		];
		$gotTree = ArrayUtils::getTreeFromList([
			'root_id'          => 1,
			'source'           => $source,
			'reader'           => false,
			'id_key_name'      => 'id',
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		]);
		$this->assertEquals($expectedTree, $gotTree);
	}



	public function testGetTreeFromList_otherRootId () {
		$gotTree = ArrayUtils::getTreeFromList([
			'root_id'          => 1,
			'source'           => $this->_sourceList,
			'reader'           => false,
			'id_key_name'      => 'id',
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		]);
		$this->assertEquals($this->_expectedTree['subtree'][1], $gotTree);
	}



	public function testGetTreeFromList_invalidParams () {
		$errorsAmount = 0;
		$requiredParamNamesList = ['source', 'parent_key_name', 'subtree_key_name'];
		foreach ($requiredParamNamesList as $paramName) {
			$params = [
				'root_id'          => 0,
				'source'           => $this->_sourceList,
				'reader'           => false,
				'id_key_name'      => 'id',
				'parent_key_name'  => 'parent_id',
				'subtree_key_name' => 'subtree',
			];
			unset($params[$paramName]);
			try {
				ArrayUtils::getTreeFromList($params);
			} catch (\InvalidArgumentException $e) {
				$errorsAmount++;
			}
		}
		$this->assertEquals(count($requiredParamNamesList), $errorsAmount);
	}



	public function testGetTreeFromDb () {
		$upIdToItemDataHash = [];
		foreach ($this->_sourceList as $itemData) {
			$upIdToItemDataHash[$itemData['parent_id']][] = $itemData;
		}
		$dbQuery = function ($query) use ($upIdToItemDataHash) {
			if (strpos($query, '=')) {
				return new \ArrayIterator([$this->_sourceList[0]]);
			} else {
				$matches = [];
				preg_match('/\(([^)]+)\)/', $query, $matches);
				$upIdsList = explode(',',$matches[1]);
				$list = [];
				foreach ($upIdsList as $upId) {
					if (isset($upIdToItemDataHash[$upId])) {
						$list = array_merge($list, $upIdToItemDataHash[$upId]);
					}
				}
				return new \ArrayIterator($list);
			}
		};
		$dbFetch = function ($r) {
			if ($r->valid()) {
				$value = $r->current();
				$r->next();
			} else {
				$value = false;
			}
			return $value;
		};
		$params = [
			'query' => [
				'SELECT * FROM users WHERE',
				'id = 123',
				'parent_id IN ()',
			],
			'db_query'         => $dbQuery,
			'db_fetch'         => $dbFetch,
			'id_key_name'      => 'id',
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		];
		//Со всеми частями запроса, заданными явно
		$gotTree = ArrayUtils::getTreeFromDb($params);
		$this->assertEquals($this->_expectedTree['subtree'][1], $gotTree);
		//Пусть запрос на выборку подразделов сгенерируется автоматически
		unset($params['query'][2]);
		$gotTree = ArrayUtils::getTreeFromDb($params);
		$this->assertEquals($this->_expectedTree['subtree'][1], $gotTree);
		//Пусть используется умолчальный id_key_name
		unset($params['id_key_name']);
		$gotTree = ArrayUtils::getTreeFromDb($params);
		$this->assertEquals($this->_expectedTree['subtree'][1], $gotTree);
	}



	public function testGetTreeFromDb_emptyResult () {
		$params = [
			'query' => [
				'SELECT * FROM users WHERE',
				'id = 123',
				'parent_id IN ()',
			],
			'db_query'         => function ($query) { return 1; },
			'db_fetch'         => function ($query) { return false; },
			'id_key_name'      => 'id',
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		];
		$gotTree = ArrayUtils::getTreeFromDb($params);
		$this->assertEquals([], $gotTree);
	}



	public function testGetTreeFromDb_invalidParams () {
		$validParams = [
			'query' => [
				'SELECT * FROM users WHERE',
				'id = 123',
				'parent_id IN ()',
			],
			'db_query'         => function ($query) { return 1; },
			'db_fetch'         => function ($query) { return false; },
			'id_key_name'      => 'id',
			'parent_key_name'  => 'parent_id',
			'subtree_key_name' => 'subtree',
		];
		$errorsAmount = 0;
		$requiredParamNamesList = ['query', 'db_query', 'db_fetch', 'parent_key_name', 'subtree_key_name'];
		foreach ($requiredParamNamesList as $paramName) {
			$params = $validParams;
			unset($params[$paramName]);
			try {
				ArrayUtils::getTreeFromList($params);
			} catch (\InvalidArgumentException $e) {
				$errorsAmount++;
			}
		}
		$this->assertEquals(count($requiredParamNamesList), $errorsAmount);
		$errorsAmount = 0;
		$invalidCasesAmount = 5;
		for ($a = 1; $a <= $invalidCasesAmount; $a++) {
			$params = $validParams;
			switch ($a) {
				case 1; unset($params['query'][2]); unset($params['query'][1]); break;
				case 2; $params['query'] = []; break;
				case 3; $params['query'] = 1; break;
				case 4; $params['db_query'] = 1; break;
				case 5; $params['db_fetch'] = 1; break;
			}
			try {
				ArrayUtils::getTreeFromList($params);
			} catch (\InvalidArgumentException $e) {
				$errorsAmount++;
			}
		}
		$this->assertEquals($invalidCasesAmount, $errorsAmount);
	}



	public function testCustomSort () {
		$list = [
			['id' => 1, 'value' => 'a'],
			['id' => 2, 'value' => 'b'],
			['id' => 3, 'value' => 'c'],
		];
		$expected = [
			['id' => 3, 'value' => 'c'],
			['id' => 1, 'value' => 'a'],
			['id' => 2, 'value' => 'b'],
		];
		$got = ArrayUtils::customSort(
			$list,
			'id',
			[3, 1, 2]
		);
		$this->assertEquals($expected, $got);
		$got = ArrayUtils::customSort(
			$list,
			'value',
			['c', 'a', 'b']
		);
		$this->assertEquals($expected, $got);

		$list = [
			['id' => 1, 'value' => 'a'],
			['id' => 2, 'value' => 'b1'],
			['id' => 2, 'value' => 'b2'],
			['id' => 3, 'value' => 'c1'],
			['id' => 3, 'value' => 'c2'],
			['id' => 3, 'value' => 'c3'],
		];
		$expected = [
			['id' => 3, 'value' => 'c1'],
			['id' => 3, 'value' => 'c2'],
			['id' => 3, 'value' => 'c3'],
			['id' => 1, 'value' => 'a'],
			['id' => 2, 'value' => 'b1'],
			['id' => 2, 'value' => 'b2'],
		];
		$got = ArrayUtils::customSort(
			$list,
			'id',
			[3, 1, 2]
		);
		$this->assertEquals($expected, $got);
	}
}
