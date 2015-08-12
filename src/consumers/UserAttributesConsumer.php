<?hh // strict

namespace FredEmmott\DefinitionFinder;

final class UserAttributesConsumer extends Consumer {
  public function getUserAttributes(): AttributeMap {
    $attrs = Map { };
    while (true) {
      list($name, $_) = $this->tq->shift();
      if (!$attrs->containsKey($name)) {
        $attrs[$name] = Vector { };
      }

      list($t, $ttype) = $this->tq->shift();
      if ($ttype === T_SR) { // this was the last attribute
        return $attrs;
      }
      if ($t === ',') { // there's another
        $this->consumeWhitespace();
        continue;
      }

      // this attribute has values
      invariant(
        $t === '(',
        "Expected attribute name to be followed by >>, (, or , at line %d; ".
        "got '%s' (%d) for attr '%s'",
        $this->tq->getLine(),
        $t,
        $ttype,
        $name,
      );

      // Possibly multiple values
      while ($this->tq->haveTokens()) {
        list($value, $ttype) = $this->tq->shift();
        switch ((int) $ttype) {
          case T_CONSTANT_ENCAPSED_STRING:
            $value = substr($value, 1, -1);
            break;
          case T_LNUMBER:
            $value = (int) $value;
            break;
          default:
            invariant_violation(
              "Invalid attribute value token type at line %d: %d",
              $this->tq->getLine(),
              $ttype
            );
        }
        $attrs[$name][] = $value;
        list($t, $_) = $this->tq->shift();
        if ($t === ')') {
          break;
        }
        invariant(
          $t === ',',
          'Expected attribute value to be followed by , or ) at line %d',
          $this->tq->getLine(),
        );
      }

      list($t, $ttype) = $this->tq->shift();
      if ($ttype === T_SR) {
        return $attrs;
      }
      invariant(
        $t === ',',
        'Expected attribute value list to be followed by >> or , at line %d',
        $this->tq->getLine(),
      );
      $this->consumeWhitespace();
    }
    invariant_violation(
      'attribute list did not end at line %d',
      $this->tq->getLine(),
    );
  }
}
