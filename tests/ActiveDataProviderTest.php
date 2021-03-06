<?php

namespace Yiisoft\Db\Sphinx\Tests;

use Yiisoft\Db\Sphinx\ActiveDataProvider;
use Yiisoft\Db\Sphinx\Query;
use yii\helpers\Yii;
use yii\web\Request;
use Yiisoft\Db\Sphinx\Tests\Data\ActiveRecord\ActiveRecord;
use Yiisoft\Db\Sphinx\Tests\Data\ActiveRecord\ArticleIndex;

/**
 * @group sphinx
 */
class ActiveDataProviderTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
    }

    // Tests :

    public function testQuery()
    {
        $query = new Query();
        $query->from('yii2_test_article_index');

        $provider = new ActiveDataProvider($this->getConnection(), $query);
        $models = $provider->getModels();
        $this->assertEquals(20, count($models));

        $provider = new ActiveDataProvider($this->getConnection(), $query);
        $provider->setPagination([
            'pageSize' => 1,
        ]);
        $models = $provider->getModels();
        $this->assertEquals(1, count($models));
    }

    public function testActiveQuery()
    {
        $provider = new ActiveDataProvider($this->getConnection(), ArticleIndex::find()->orderBy('id ASC'));
        $models = $provider->getModels();
        $this->assertEquals(20, count($models));
        $this->assertTrue($models[0] instanceof ArticleIndex);
        $this->assertTrue($models[1] instanceof ArticleIndex);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20], $provider->getKeys());

        $provider = new ActiveDataProvider($this->getConnection(), ArticleIndex::find());
        $provider->setPagination([
            'pageSize' => 1,
        ]);
        $models = $provider->getModels();
        $this->assertEquals(1, count($models));
    }

    /**
     * @depends testQuery
     */
    public function testFacetQuery()
    {
        $query = new Query();
        $query->from('yii2_test_article_index');
        $query->facets([
            'author_id'
        ]);

        $provider = new ActiveDataProvider($this->getConnection(), $query);
        $models = $provider->getModels();
        $this->assertEquals(20, count($models));
        $this->assertEquals(10, count($provider->getFacet('author_id')));
    }

    /**
     * @depends testQuery
     */
    public function testTotalCountFromMeta()
    {
        $query = (new Query())
            ->from('yii2_test_article_index')
            ->showMeta(true);

        $provider = new ActiveDataProvider($this->getConnection(), $query);
        $provider->setPagination(['pageSize' => 1]);
        $models = $provider->getModels();
        $this->assertEquals(1, count($models));
        $this->assertEquals(1002, $provider->getTotalCount());
    }

    /**
     * @depends testTotalCountFromMeta
     *
     * @see https://github.com/yiisoft/yii2-sphinx/issues/11
     */
    public function testAutoAdjustPagination()
    {
        $request = new Request();
        $request->setQueryParams(['page' => 2]);
        Yii::getApp()->set('request', $request);

        $query = (new Query())
            ->from('yii2_test_article_index')
            ->orderBy(['id' => SORT_ASC])
            ->showMeta(true);

        $provider = new ActiveDataProvider($this->getConnection(), $query);
        $provider->setPagination(['pageSize' => 1]);
        $models = $provider->getModels();
        $this->assertEquals(2, $models[0]['id']);
    }

    /**
     * @depends testAutoAdjustPagination
     *
     * @see https://github.com/yiisoft/yii2-sphinx/issues/12
     */
    public function testAutoAdjustMaxMatches()
    {
        $request = new Request();
        $request->setQueryParams(['page' => 99999]);
        Yii::getApp()->set('request', $request);

        $query = (new Query())
            ->from('yii2_test_article_index')
            ->orderBy(['id' => SORT_ASC]);

        $provider = new ActiveDataProvider($this->getConnection(), $query);
        $provider->setPagination(['pageSize' => 100, 'validatePage' => false,]);
        $models = $provider->getModels();
        $this->assertEmpty($models); // no exception
    }

    public function testMatch()
    {
        $query = (new Query())
            ->from('yii2_test_article_index')
            ->match('Repeated');

        $provider = new ActiveDataProvider($this->getConnection(), $query);

        $this->assertEquals(1002, $provider->getTotalCount());

        $query->match('Excepturi');
        $provider = new ActiveDataProvider($this->getConnection(), $query);

        $this->assertEquals(29, $provider->getTotalCount());
    }
}
