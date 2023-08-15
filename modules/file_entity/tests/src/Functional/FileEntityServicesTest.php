<?php

namespace Drupal\Tests\file_entity\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\rest\Entity\RestResourceConfig;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;

/**
 * Tests File entity REST services
 *
 * @group file_entity
 */
class FileEntityServicesTest extends FileEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array(
    'node',
    'hal',
    'rest'
  );

  /**
   * Tests that a file field is correctly handled with REST.
   */
  public function testFileFieldREST() {

    $format = 'hal_json';

    $resource = RestResourceConfig::create([
      'id' => 'node',
      'plugin_id' => 'entity:node',
      'granularity' => 'resource',
      'configuration' => [
        'methods' => ['GET', 'POST'],
        'formats' => [$format],
        'authentication' => ['cookie'],
      ],
      'status' => TRUE,
    ]);
    $resource->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $this->drupalCreateContentType(['name' => 'resttest', 'type' => 'resttest']);

    // Grant create access to anonymous role.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, [
      'access content',
      'create resttest content',
    ]);

    // Add a file field to the resttest content type.
    $file_field_storage = FieldStorageConfig::create(array(
      'type' => 'file',
      'entity_type' => 'node',
      'field_name' => 'field_file',
    ));
    $file_field_storage->save();
    $file_field = FieldConfig::create(array(
      'field_storage' => $file_field_storage,
      'entity_type' => 'node',
      'bundle' => 'resttest',
    ));
    $file_field->save();

    // Create a file.
    $file_uri = 'public://' . $this->randomMachineName() . '.txt';
    file_put_contents($file_uri, 'This is some file contents');
    $file = File::create(array('uri' => $file_uri, 'status' => FILE_STATUS_PERMANENT, 'uid' => 1));
    $file->save();

    // Create a node with a file.
    $node = Node::create(array(
      'title' => 'A node with a file',
      'type' => 'resttest',
      'field_file' => array(
        'target_id' => $file->id(),
        'display' => 0,
        'description' => 'An attached file',
      ),
      'status' => TRUE,
    ));
    $node->save();

    // GET node.
    $client = $this->getHttpClient();
    $url = $node->toUrl()->setAbsolute(TRUE)->setRouteParameter('_format', $format);
    $response = $client->request('GET', $url->toString());
    $this->assertEquals(200, $response->getStatusCode());
    $response_data = Json::decode((string) $response->getBody());

    // Test that field_file refers to the file entity.
    $normalized_field = $response_data['_embedded'][$this->getAbsoluteUrl('/rest/relation/node/resttest/field_file')];
    $this->assertEquals($file->toUrl()->setAbsolute()->setRouteParameter('_format', $format)->toString(), $normalized_field[0]['_links']['self']['href']);
    $this->assertEquals('An attached file', $normalized_field[0]['description']);

    // Remove the node.
    $node->delete();
    try {
      $client->request('GET', $url->toString());
      $this->fail('Client exception not thrown');
    }
    catch (ClientException $e) {
      $this->assertEquals(404, $e->getResponse()->getStatusCode());
    }

    // POST node to create new.
    unset($response_data['nid']);
    unset($response_data['created']);
    unset($response_data['changed']);
    unset($response_data['status']);
    unset($response_data['promote']);
    unset($response_data['sticky']);
    unset($response_data['revision_timestamp']);
    unset($response_data['_embedded'][$this->getAbsoluteUrl('/rest/relation/node/resttest/uid')]);
    unset($response_data['_embedded'][$this->getAbsoluteUrl('/rest/relation/node/resttest/revision_uid')]);

    $serialized = Json::encode($response_data);

    $request_options = [];
    $request_options[RequestOptions::BODY] = $serialized;
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/hal+json';

    $post_url = Url::fromUri('base:/node')->setOption('query', ['_format' => $format])->setAbsolute();
    $response = $client->request('POST', $post_url->toString(), $request_options);
    $this->assertEquals(201, $response->getStatusCode());

    // Test that the new node has a valid file field.
    $nodes = Node::loadMultiple();
    $last_node = array_pop($nodes);
    $this->assertEquals($last_node->get('field_file')->target_id, $file->id());
  }

}
