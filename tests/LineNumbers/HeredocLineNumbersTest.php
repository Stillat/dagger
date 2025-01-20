<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('it doesnt add line numbers to end of heredoc', function () {
    $template = <<<'PHP'

    <?php
    $someString = <<<END
one
    two
        three
    four
    
    five
END;

?>
PHP;

    $expected = <<<'EXPECTED'
/**  |---LINE:1---| */
    /**  |---LINE:2---| */<?php
/**  |---LINE:3---| */    $someString = <<<END
/**  |---LINE:4---| */one
/**  |---LINE:5---| */    two
/**  |---LINE:6---| */        three
/**  |---LINE:7---| */    four
/**  |---LINE:8---| */    
/**  |---LINE:9---| */    five
END;
/**  |---LINE:11---| */
/**  |---LINE:12---| */?>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->insertLineNumbers($template)
    );
});
