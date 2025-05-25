<?php

namespace HelpPlatform\Tests;

use WP_UnitTestCase;

class Basic_Test extends WP_UnitTestCase {

    public function test_plugin_loaded() {
        $this->assertTrue( defined( 'HELP_PLATFORM_VERSION' ) );
        $this->assertTrue( defined( 'HELP_PLATFORM_PLUGIN_DIR' ) );
        $this->assertTrue( defined( 'HELP_PLATFORM_PLUGIN_URL' ) );
    }

    public function test_plugin_constants() {
        $this->assertNotEmpty( HELP_PLATFORM_VERSION );
        $this->assertNotEmpty( HELP_PLATFORM_PLUGIN_DIR );
        $this->assertNotEmpty( HELP_PLATFORM_PLUGIN_URL );
    }

    public function test_plugin_functions() {
        $this->assertTrue( function_exists( 'help_platform_get_plugin_data' ) );
        $this->assertTrue( function_exists( 'help_platform_get_plugin_url' ) );
    }

} 