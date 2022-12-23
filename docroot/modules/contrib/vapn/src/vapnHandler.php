<?php

namespace Drupal\vapn;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Class vapnHandler.
 */
class vapnHandler {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Constructs a new vapnHandler object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function getDefaultsForNode($form_state){
    if(!($node = $form_state->getFormObject()->getEntity())){
      return [];
    }
    $records = \Drupal::database()->select('vapn')
      ->fields('vapn', array('rid'))
      ->condition('nid', $node->id())
      ->execute();
    $col = $records->fetchCol();
    return empty($col) ? [] : array_values($col);
  }

  public function checkIfContentTypeEnabled(){
    if(!\Drupal::currentUser()->hasPermission('use vapn')){
      return FALSE;
    }
    /** @var \Drupal\node\Entity\NodeType $nodeType */
    $routeName = \Drupal::routeMatch()->getRouteName();
    $config = \Drupal::config('vapn.vapnconfig')->get('vapn_node_list');
    $nodeType = '';
    if($routeName == 'node.add') {
      $nodeType = \Drupal::routeMatch()->getParameters()->get('node_type');
      $nodeType = $nodeType->get('type');
    } elseif ($routeName == 'entity.node.edit_form'){
      /** @var NodeInterface $nodeType */
      $nodeType = \Drupal::routeMatch()->getParameters()->get('node');
      $nodeType = $nodeType->getType();
    }
    if(is_null($config)){
      return FALSE;
    }
    return in_array($nodeType,$config,TRUE);
  }

  public function cleanEntriesByEntityId($id) {
    $query = \Drupal::database()->delete('vapn');
    $query->condition('nid', $id);
    $count = $query->execute();
    return $count > 0 ? TRUE : FALSE;
  }


  public function insertRoleEntry($nid, $rid) {
    //aggiorna
    $connection = \Drupal::database();
    $connection->insert('vapn')
      ->fields([
        'nid' => $nid,
        'rid' => $rid,
      ])
      ->execute();
  }
}
