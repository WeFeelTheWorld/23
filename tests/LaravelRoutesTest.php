<?php

class LaravelRoutesTest extends TestCase
{
    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testLandingResponseCode()
    {
        $response = $this->call('GET', '/');

        $this->assertEquals(200, $response->status());
    }

    public function testServeAngular()
    {
        $this->visit('/')
             ->see('ng-app');
    }

    public function testUnsupportedBrowserPage()
    {
        $this->visit('/unsupported-browser')
             ->see('update your browser')
             ->see('Internet Explorer');
    }
    public function testStyleResponse(){
      //$this->get('/api/index/epi/structure')
        //->seeJsonContains(['style_id' => 0])
        //->seeJsonContains(['style' => null]);
    }
}
