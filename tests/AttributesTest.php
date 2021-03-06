<?hh // strict

namespace FredEmmott\DefinitionFinder\Test;

use FredEmmott\DefinitionFinder\FileParser;
use FredEmmott\DefinitionFinder\ScannedBase;
use FredEmmott\DefinitionFinder\ScannedClass;
use FredEmmott\DefinitionFinder\ScannedFunction;

class AttributesTest extends \PHPUnit_Framework_TestCase {
  private \ConstVector<ScannedClass> $classes = Vector {};
  private \ConstVector<ScannedFunction> $functions = Vector {};

  protected function setUp(): void {
    $parser = FileParser::FromFile(
      __DIR__.'/data/attributes.php'
    );
    $this->classes = $parser->getClasses();
    $this->functions = $parser->getFunctions();
  }

  public function testSingleSimpleAttribute(): void {
    $class = $this->findClass('ClassWithSimpleAttribute');
    $this->assertEquals(
      Map { "Foo" => Vector { } },
      $class->getAttributes(),
    );
  }

  public function testMultipleSimpleAttributes(): void {
    $class = $this->findClass('ClassWithSimpleAttributes');
    $this->assertEquals(
      Map { "Foo" => Vector { }, "Bar" => Vector { } },
      $class->getAttributes(),
    );
  }

  public function testWithSingleStringAttribute(): void {
    $class = $this->findClass('ClassWithStringAttribute');
    $this->assertEquals(
      Map { 'Herp' => Vector {'derp'} },
      $class->getAttributes(),
    );
  }

  public function testWithFormattedAttributes(): void {
    $class = $this->findClass('ClassWithFormattedAttributes');
    $this->assertEquals(
      Map { 'Foo' => Vector { }, 'Bar' => Vector {'herp', 'derp'} },
      $class->getAttributes(),
    );
  }

  public function testWithFormattedArrayAttribute(): void {
    $class = $this->findClass('ClassWithFormattedArrayAttribute');
    $this->assertEquals(
      Map { 'Bar' => Vector {['herp']} },
      $class->getAttributes(),
    );
  }

  public function testWithSingleIntAttribute(): void {
    $class = $this->findClass('ClassWithIntAttribute');
    $this->assertEquals(
      Map { 'Herp' => Vector {123} },
      $class->getAttributes(),
    );
    // Check it's an int, not a string
    $this->assertSame(
      123,
      $class->getAttributes()['Herp'][0],
    );
  }

  public function testFunctionHasAttributes(): void {
    $func = $this->findScanned($this->functions, 'function_after_classes');
    $this->assertEquals(
      Map { 'FunctionFoo' => Vector { } },
      $func->getAttributes(),
    );
  }

  public function testFunctionContainingBitShift(): void {
    $data = '<?hh function foo() { 1 << 3; }';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEmpty($fun->getAttributes());
  }

  public function testFunctionAttrsDontPolluteClass(): void {
    $class = $this->findClass('ClassAfterFunction');
    $this->assertEquals(
      Map { 'ClassFoo' => Vector {} },
      $class->getAttributes(),
    );
  }

  public function testParameterHasAttribute(): void {
    $data = '<?hh function foo(<<Bar>> $baz) {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $params = $fun->getParameters();
    $this->assertEquals(
      Vector { 'baz' },
      $params->map($x ==> $x->getName()),
    );

    $this->assertEquals(
      Vector { Map { 'Bar' => Vector { } } },
      $params->map($x ==> $x->getAttributes()),
    );
  }

  public function attributeExpressions(): array<(string,mixed)> {
    return array(
      tuple("'herp'.'derp'", 'herpderp'),
      tuple("Foo\\Bar::class", "Foo\\Bar"),
      tuple("true", true),
      tuple("false", false),
      tuple("null", null),
      tuple("INF", INF),
      tuple("+123", 123),
      tuple("-123", -123),
      tuple('array()', []),
      tuple('[]', []),
      tuple('array(123)', [123]),
      tuple('array(123,)', [123]),
      tuple('array(123,456)', [123,456]),
      tuple('array(123,456,)', [123,456]),
      tuple('[123,456]', [123,456]),
      tuple('[123 , 456]', [123,456]),
      tuple('[123 => 456]', [123 => 456]),
      tuple('shape()', []),
      tuple(
        'shape("foo" => "bar", "herp" => 123)',
        shape('foo' => 'bar', 'herp' => 123),
      ),
    );
  }

  /**
   * @dataProvider attributeExpressions
   */
  public function testAttributeExpression(
    string $source,
    mixed $expected,
  ): void {
    $data = '<?hh <<MyAttr('.$source.')>> function foo(){}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      Map { 'MyAttr' => Vector { $expected } },
      $fun->getAttributes(),
    );
  }

  private function findScanned<T as ScannedBase>(
    \ConstVector<T> $container,
    string $name,
  ): T {
    foreach ($container as $scanned) {
      if ($scanned->getName() === "FredEmmott\\DefinitionFinder\\Test\\".$name) {
        return $scanned;
      }
    }
    invariant_violation('Could not find scanned %s', $name);
  }

  private function findClass(string $name): ScannedClass {
    return $this->findScanned($this->classes, $name);
  }
}
