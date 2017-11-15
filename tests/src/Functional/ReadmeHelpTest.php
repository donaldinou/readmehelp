<?php

namespace Drupal\Tests\readmehelp\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests markdown into markup conversion.
 *
 * @group help
 */
class ReadmeHelpTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'filter',
    'help',
    'readmehelp',
    'readmehelp_test',
  ];

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['access administration pages']);
  }

  /**
   * Verifies markup generated by the readmehelp_markdown filter.
   *
   * @see \Drupal\readmehelp\Plugin\Filter\ReadmehelpMarkdown
   * @see \Drupal\readmehelp\ReadmeHelpMarkdownConverter::convertMarkdownFile()
   */
  public function testReadmehelpMarkdown() {
    $this->drupalLogin($this->adminUser);
    $depender = in_array('readmehelp', system_get_info('module', 'readmehelp_test')['dependencies']);
    $this->assertTrue($depender, 'The module is dependent on the readmehelp.');
    $implements_hook_help = \Drupal::moduleHandler()->implementsHook('readmehelp_test', 'help');
    $this->assertFalse($implements_hook_help, 'The hook_help is not implemented on the module.');
    $this->drupalGet('admin/help');
    $this->assertSession()->linkExistsExact('readmehelp_test');
    $this->getSession()->getPage()->clickLink('readmehelp_test');
    $this->assertSession()->addressMatches('/\/admin\/help\/readmehelp_test$/');
    // Ensure the actual help page is displayed to avoid a false positive.
    $this->assertResponse(200);

    $this->assertSession()->responseContains('<h1><a id="readme-help-test-module" href="#readme-help-test-module" class="anchor">#</a> README Help Test module</h1>');
    $this->assertSession()->responseContains('<strong>Asterisk Bold</strong>');
    $this->assertSession()->responseContains('<strong>Underscore Bold</strong>');
    $this->assertSession()->responseContains('<em>Asterisk Italic</em>');
    $this->assertSession()->responseContains('<em>Underscore Italic</em>');
    $this->assertSession()->responseContains('<code class="code--singleline">inline code block</code>');

    $code_multiline = <<<'HTML'
<pre><code class="code--multiline">
// Multiline code block

function test() {
  echo "Test";
  $test = [
    1,
    2,
  ];
}
</code></pre>
HTML;

    $this->assertSession()->responseContains($code_multiline);

    $blockquote_multiline = <<<'HTML'
<blockquote><a id="blockquote-test-test-test-test" href="#blockquote-test-test-test-test" class="anchor">&gt; </a>Blockquote test test test test test test test test test test test test test
test test test test test test test test test test test test test test test test
test test test test test test test test test test test test test test test test.</blockquote>
HTML;

    $this->assertSession()->responseContains($blockquote_multiline);

    $cite_multiline = <<<'HTML'
<cite><a id="cite-test-test-test-test-test" href="#cite-test-test-test-test-test" class="anchor">&gt;&gt; </a>Cite test test test test test test test test test test test test test
test test test test test test test test test test test test test test test test
test test test test test test test test test test test test test test test test.</cite>
HTML;

    $this->assertSession()->responseContains($cite_multiline);
    $this->assertSession()->linkExistsExact('Anchor relative path test');
    $this->assertSession()->linkExistsExact('PHP');
    $this->assertSession()->linkExistsExact('https://www.drupal.org/');

    $path = \Drupal::moduleHandler()->getModule('readmehelp_test')->getPath();
    $host = explode('/admin/help/readmehelp_test', $this->getSession()->getCurrentUrl())[0];
    $src = "$host/$path/images/druplicon.png";
    $this->assertSession()->responseMatches('{<img src="' . $src . '" alt="ALT Text" title="Image relative path test" class="markdown-image"\ ?\/?>}');

    $this->assertSession()->responseMatches('{<img src="https://raw.githubusercontent.com/drugan/readmehelp/8.x-1.x/images/drupalcat.png" alt="ALT Text" title="Image absolute path test" class="markdown-image"\ ?\/?>}');

    $unordered_list = <<<'HTML'
<ul class="ul"><li>Unordered list ITEM_1</li>
<li>Unordered list long line test test test test test test test test test test
test test test test test test test test test test test ITEM_2</li>
</ul>
HTML;

    $this->assertSession()->responseContains($unordered_list);

    $ordered_list = <<<'HTML'
<ol class="ol"><li>Ordered list ITEM_1</li>
<li>Ordered list long line test test test test test test test test test test
test test test test test test test test test test test ITEM_2</li>
</ol>
HTML;

    $this->assertSession()->responseContains($ordered_list);

    $this->assertSession()->assertNoEscaped($this->getSession()->getPage()->getHtml());
    $this->assertSession()->pageTextContains('Backslashed asterisk: *');
    $this->assertSession()->pageTextContains('Backslashed backtick: `');
    $this->assertSession()->pageTextContains('Backslashed dash: -');
    $this->assertSession()->pageTextContains('Backslashed underscore: _');
    $this->assertSession()->pageTextContains('Backslashed anchor: []()');
    $this->assertSession()->pageTextContains('# Backslashed leading hash');
    $this->assertSession()->linkByHrefNotExists('#backslashed-leading-hash');
    $this->assertSession()->pageTextContains('> Backslashed line leading greater than symbol');
    $this->assertSession()->linkByHrefNotExists('#backslashed-line-leading-greater');
    $this->assertSession()->responseMatches('/<hr class="hr-underscore"\ ?\/?>/');
    $this->assertSession()->responseMatches('/<hr class="hr-asterisk"\ ?\/?>/');
    $this->assertSession()->responseMatches('/<hr class="hr-dash"\ ?\/?>/');

    $this->assertSession()->responseContains('<h2><a id="h2" href="#h2" class="anchor">#</a> H2</h2>');
    $this->assertSession()->responseContains('<h2><a id="alternative-h2" href="#alternative-h2" class="anchor"># </a>Alternative H2</h2>');
    $this->assertSession()->responseContains('<h3><a id="h3" href="#h3" class="anchor">#</a> H3</h3>');
    $this->assertSession()->responseContains('<h4><a id="h4" href="#h4" class="anchor">#</a> H4</h4>');
    $this->assertSession()->responseContains('<h5><a id="h5" href="#h5" class="anchor">#</a> H5</h5>');
    $this->assertSession()->responseContains('<h6><a id="h6" href="#h6" class="anchor">#</a> H6</h6>');
  }

  /**
   * Verifies markup generated by the PHP highlight_file() function.
   *
   * @see \Drupal\readmehelp\ReadmeHelpMarkdownConverter::insertPhpSnippets()
   */
  public function testInsertPhpSnippets() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/help/readmehelp_test');
    // Ensure the actual help page is displayed to avoid a false positive.
    $this->assertResponse(200);
    // The PHP file tokens should be replaced by the specified snippets.
    $this->assertSession()->pageTextNotContains('@PHPFILE: readmehelp_test/readmehelp_test.module LINE:19 PADD:1 :PHPFILE@');
    $this->assertSession()->pageTextNotContains('@PHPFILE: readmehelp_test/readmehelp_test.module :PHPFILE@');
    $snippet = $this->getSession()->getPage()->findAll('css', '.highlighted-snippet');
    $this->assertEquals('table', $snippet[0]->getTagName());
    $this->assertEquals('table', $snippet[1]->getTagName());
    $strlen_0 = strlen($snippet[0]->getText());
    $strlen_1 = strlen($snippet[1]->getText());
    $this->assertEquals(87, $strlen_0, "The expected snippet length 87 is equal to actual $strlen_0.");
    $this->assertEquals(298, $strlen_1, "The expected snippet length 298 is equal to actual $strlen_1.");
  }

}
