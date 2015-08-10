<?hh // strict

namespace FredEmmott\DefinitionFinder\Test;

use FredEmmott\DefinitionFinder\FileParser;
use FredEmmott\DefinitionFinder\ScannedClass;
use FredEmmott\DefinitionFinder\ScannedMethod;
use FredEmmott\DefinitionFinder\ScannedTypehint;

class TuplesTest extends \PHPUnit_Framework_TestCase {
  public function testTupleReturnType(): void {
    $data = '<?hh

<<__Native>>
function foo(): (string, string);
';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      [
        'tuple',
        [
          ['string', []],
          ['string', []],
        ],
      ],
      $this->sthToArray($function->getReturnType()),
    );
  }

  public function testContainerOfTuples(): void {
    $data = '<?hh

<<__Native>>
function foo(): Vector<(string, string)>;
';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $return_type = $function->getReturnType();

    $this->assertEquals(
      [
        'Vector',
        [
          [
            'tuple',
            [
              ['string', []],
              ['string', []],
            ],
          ],
        ],
      ],
      $this->sthToArray($function->getReturnType()),
    );
  }

  public function testTupleParameterType(): void {
    $data = '<?hh

function foo((string, string) $bar) {};
';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    // Test still at least checks they're not a parse error
    $this->markTestIncomplete('Parameters not currently exposed to API');
  }

  private function sthToArray(?ScannedTypehint $typehint): ?array<mixed> {
    if ($typehint === null) {
      return null;
    }

    $generics = $typehint->getGenerics()->map(
      $x ==> $this->sthToArray($x),
    )->toArray();

    return [$typehint->getTypehint(), $generics];
  }
}