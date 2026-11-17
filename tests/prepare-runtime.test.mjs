import test from 'node:test';
import assert from 'node:assert/strict';
import { unzipSync, zipSync, strToU8, strFromU8 } from 'fflate';
import { normalizeWordPressZip } from '../scripts/prepare-runtime.mjs';

test('GitHub WordPress archives normalize to the root expected by Playground', () => {
    const source = zipSync({
        'WordPress-6.9/wp-load.php': strToU8('<?php echo "WordPress";'),
        'WordPress-6.9/wp-content/index.php': strToU8('<?php // Silence.'),
        'unrelated/readme.txt': strToU8('outside the selected WordPress folder'),
    });
    const result = unzipSync(normalizeWordPressZip(source));
    assert.deepEqual(Object.keys(result).sort(), ['wordpress/wp-content/index.php', 'wordpress/wp-load.php']);
    assert.equal(strFromU8(result['wordpress/wp-load.php']), '<?php echo "WordPress";');
});

test('a download without WordPress files cannot become a runtime archive', () => {
    assert.throws(
        () => normalizeWordPressZip(zipSync({ 'readme.txt': strToU8('not WordPress') })),
        /wp-load.php/,
    );
});

test('archive path traversal is rejected', () => {
    const source = zipSync({
        'WordPress-6.9/wp-load.php': strToU8('<?php'),
        'WordPress-6.9/../outside.php': strToU8('<?php'),
    });
    assert.throws(() => normalizeWordPressZip(source), /Недопустимый путь/);
});
