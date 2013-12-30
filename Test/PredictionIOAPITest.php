<?php
/**
 * PredictionIOAPITest
 *
 * A small test suite for the PredictionIOAPI class
 */

namespace predictionio\Test;

use IdeaCouture\PredictionIOAPI;
use Mockery as M;

/**
 * PhpUnit class to test the PredictionIOAPI class
 *
 * @since	1.0.0
 */
class PredictionIOAPITest extends \PHPUnit_Framework_TestCase
{
	protected $client;

	public function setUp() {
		// Mock the client
		$this->client = M::mock('PredictionIO\PredictionIOClient');
	}

	public function tearDown()
	{
		M::close();
	}
	public function testConstructorSetsRecommendationEngine()
	{
		// Recommendation Engine
		$recommendationEngine = 'RecEngine';

		// Create the PredictionIOAPI object
		$predictionIOAPI = new PredictionIOAPI($this->client, $recommendationEngine);

		// Test that the Recommendation Engine is set
		$this->assertEquals($recommendationEngine, $predictionIOAPI->recommendation_engine);
	}

	public function testConstructorSetsSimilarityEngine()
	{
		// Similarity Engine
		$similarityEngine = 'SimEngine';

		// Create the PredictionIOAPI object
		$predictionIOAPI = new PredictionIOAPI($this->client, null, $similarityEngine);

		// Test that the Similarity Engine is set
		$this->assertEquals($similarityEngine, $predictionIOAPI->similarity_engine);
	}

	public function testAddUser()
	{
		// Build out the mocked objects' methods
		// $this->client->shouldReceive('getCommand')
		// 	->with('create_user')
		// 	->once()
		// 	->andReturn('dude');
	}

	public function testAddUserDoesNotOverwrite()
	{

	}

}