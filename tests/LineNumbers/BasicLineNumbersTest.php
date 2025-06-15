<?php

test('it inserts line numbers within basic php', function () {
    $template = <<<'EOT'
<?php
$some = 'basic php';

$some .= 'that';

if ($this
) {
// Something here.
}
?>
EOT;

    $expected = <<<'EXPECTED'
/**  |---LINE:1---| */<?php
/**  |---LINE:2---| */$some = 'basic php';
/**  |---LINE:3---| */
/**  |---LINE:4---| */$some .= 'that';
/**  |---LINE:5---| */
/**  |---LINE:6---| */if ($this
/**  |---LINE:7---| */) {
/**  |---LINE:8---| */// Something here.
/**  |---LINE:9---| */}
/**  |---LINE:10---| */?>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->insertLineNumbers($template)
    );
});

test('it inserts line numbers with multiple php blocks', function () {
    $template = <<<'EOT'
<?php
$some = 'basic php';

$some .= 'that';

if ($this
) {
// Something here.
}
?> some php stuff!

<?php
$some = 'basic php';

$some .= 'that';

if ($this
) {
// Something here.
}
?>

some more stuff
EOT;

    $expected = <<<'EXPECTED'
/**  |---LINE:1---| */<?php
/**  |---LINE:2---| */$some = 'basic php';
/**  |---LINE:3---| */
/**  |---LINE:4---| */$some .= 'that';
/**  |---LINE:5---| */
/**  |---LINE:6---| */if ($this
/**  |---LINE:7---| */) {
/**  |---LINE:8---| */// Something here.
/**  |---LINE:9---| */}
/**  |---LINE:10---| */?> some php stuff!

/**  |---LINE:12---| */<?php
/**  |---LINE:13---| */$some = 'basic php';
/**  |---LINE:14---| */
/**  |---LINE:15---| */$some .= 'that';
/**  |---LINE:16---| */
/**  |---LINE:17---| */if ($this
/**  |---LINE:18---| */) {
/**  |---LINE:19---| */// Something here.
/**  |---LINE:20---| */}
/**  |---LINE:21---| */?>

some more stuff
EXPECTED;

    $this->assertSame(
        $expected,
        $this->insertLineNumbers($template)
    );
});

test('it adds comments around regular Blade line numbers to help prevent invalid php code', function () {
    $template = <<<'BLADE'
@php
use function Stillat\Dagger\component;

component()->cache();
@endphp

{{ $slot ?? '' }}
BLADE;

    $expected = <<<'EXPECTED'
/**  |---LINE:1---| *//** |---LINE:1---| */@php
use function Stillat\Dagger\component;

component()->cache();
/** |---LINE:5---| */@endphp

/** |---LINE:7---| */{{ $slot ?? '' }}
EXPECTED;

    $this->assertSame(
        $expected,
        $this->insertLineNumbers($template)
    );
});
