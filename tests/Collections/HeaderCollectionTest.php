<?php

use Everest\Http\Collections\HeaderCollection;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class HeaderCollectionTest extends \PHPUnit\Framework\TestCase {


  public function testEmptyCollection()
  {
    $col = new HeaderCollection;

    $this->assertEquals([], $col->toArray());
    $this->assertEquals([], $col->get('unset-name'));
    $this->assertFalse($col->has('unset-name'));
  }

  public function testSetScalar()
  {
    $col = new HeaderCollection;
    $col->set('name', 'value');

    $this->assertTrue($col->has('name'));
    $this->assertEquals(['value'], $col->get('name'));
    $this->assertEquals(['name' => ['value']], $col->toArray());
  }

  public function testSetArray()
  {
    $col = new HeaderCollection;
    $col->set('name', ['value']);

    $this->assertTrue($col->has('name'));
    $this->assertEquals(['value'], $col->get('name'));
    $this->assertEquals(['name' => ['value']], $col->toArray());
  }

  public function testWith()
  {
    $col = new HeaderCollection;
    $newCol = $col->with('name', 'value');

    $this->assertNotEquals($col, $newCol);
    $this->assertTrue($newCol->has('name'));
    $this->assertEquals(['value'], $newCol->get('name'));
    $this->assertEquals(['name' => ['value']], $newCol->toArray());

    // Equal values dont create new object
    $newerCol = $newCol->with('name', 'value');
    $this->assertEquals($newCol, $newerCol);
  }

  public function testPush()
  {
    $col = new HeaderCollection([
      'set' => 'value1',
      'array' => ['value1', 'value2']
    ]);

    $col->push('set', 'value2');
    $this->assertEquals(['value1', 'value2'], $col->get('set'));

    $col->push('array', 'value3');
    $this->assertEquals(['value1', 'value2', 'value3'], $col->get('array'));

    $col->push('unset', 'value');
    $this->assertEquals(['value'], $col->get('unset'));
  }

  public function testWithAdded()
  {
    $col = new HeaderCollection(['set' => 'value1']);

    $col2 = $col->withAdded('set', 'value2');
    $this->assertNotEquals($col, $col2);

    $this->assertEquals(['value1'], $col->get('set'));
    $this->assertEquals(['value1', 'value2'], $col2->get('set'));

    $col3 = $col->withAdded('unset', 'value');
    $this->assertNotEquals($col, $col3);

    $this->assertEquals([], $col->get('unset'));
    $this->assertEquals(['value'], $col3->get('unset'));
  }

  public function testDelete()
  {
    $col = new HeaderCollection(['set' => 'value']);
    $col->delete('set');

    $this->assertEquals([], $col->get('set'));
    $this->assertFalse($col->has('set'));
  }

  public function testWithout()
  {
    $col = new HeaderCollection(['set' => 'value']);
    $col2 = $col->without('set');

    // Initial collection remains untouched
    $this->assertTrue($col->has('set'));
    $this->assertEquals(['value'], $col->get('set'));
    
    // New collection has not element `set`
    $this->assertFalse($col2->has('set')); 
    $this->assertEquals([], $col2->get('set'));

    // Initial collection returned if unchanged
    $col3 = $col->without('unset');
    $this->assertEquals($col3, $col);
  }

  public function testCount()
  {
    $col = new HeaderCollection;
    $this->assertEquals(0, count($col));

    $col->set('name', 'value');
    $this->assertEquals(1, count($col));
  }

  public function testGetIterator()
  {
    $col = new HeaderCollection;
    $this->assertInstanceOf(\Iterator::CLASS, $col->getIterator());
  }

  public function testToString()
  {
    $col = new HeaderCollection(['set' => 'value']);
    $this->assertTrue(is_string((string)$col));
  }
}
