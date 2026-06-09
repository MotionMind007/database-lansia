<?php

namespace Tests\Unit;

use App\Support\SearchHelper;
use PHPUnit\Framework\TestCase;

class SearchHelperTest extends TestCase
{
    public function test_escapes_percent_wildcard(): void
    {
        $this->assertSame('100\\% discount', SearchHelper::escapeLike('100% discount'));
    }

    public function test_escapes_underscore_wildcard(): void
    {
        $this->assertSame('user\\_name', SearchHelper::escapeLike('user_name'));
    }

    public function test_escapes_backslash(): void
    {
        $this->assertSame('path\\\\to\\\\file', SearchHelper::escapeLike('path\\to\\file'));
    }

    public function test_handles_combined_special_chars(): void
    {
        $this->assertSame('50\\% off\\_sale\\\\today', SearchHelper::escapeLike('50% off_sale\\today'));
    }

    public function test_leaves_normal_text_unchanged(): void
    {
        $this->assertSame('Maria Papua', SearchHelper::escapeLike('Maria Papua'));
    }

    public function test_handles_empty_string(): void
    {
        $this->assertSame('', SearchHelper::escapeLike(''));
    }

    public function test_handles_unicode_characters(): void
    {
        $this->assertSame('Ñoño 100\\%', SearchHelper::escapeLike('Ñoño 100%'));
    }
}
