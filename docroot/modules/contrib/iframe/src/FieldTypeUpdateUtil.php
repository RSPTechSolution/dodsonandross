<?php

namespace Drupal\iframe;

use Drupal;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

class FieldTypeUpdateUtil {

  /**
   * Helper function for HOOK_Update to update the field schema columns.
   *
   * Based on address.install (thanks to the maintainer!)
   *
   * @param $field_type The field type id.
   * @param array $columns_to_add array of the column names from schema() function.
   */
  public static function _field_type_schema_column_add_helper($field_type, array $columns_to_add = array()) {
    $processed_fields = [];
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $field_definition = $field_type_manager->getDefinition($field_type);
    $field_item_class = $field_definition['class'];
  
    $schema = \Drupal::database()->schema();
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $entity_field_map = $entity_field_manager->getFieldMapByFieldType($field_type);
    // The key-value collection for tracking installed storage schema.
    $entity_storage_schema_sql = \Drupal::keyValue('entity.storage_schema.sql');
    $entity_definitions_installed = \Drupal::keyValue('entity.definitions.installed');
  
    foreach ($entity_field_map as $entity_type_id => $field_map) {
      $entity_storage = $entity_type_manager->getStorage($entity_type_id);
  
      // Only SQL storage based entities are supported / throw known exception.
      //    if (!($entity_storage instanceof SqlContentEntityStorage)) {
      //      continue;
      //    }
  
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
      /** @var Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $entity_storage->getTableMapping($field_storage_definitions);
      // Only need field storage definitions of our field type:
      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
      foreach (array_intersect_key($field_storage_definitions, $field_map) as $field_storage_definition) {
        $field_name = $field_storage_definition->getName();
        try {
          $table = $table_mapping->getFieldTableName($field_name);
        } catch (SqlContentEntityStorageException $e) {
          // Custom storage? Broken site? No matter what, if there is no table
          // or column, there's little we can do.
          continue;
        }
        // See if the field has a revision table.
        $revision_table = NULL;
        if ($entity_type->isRevisionable() && $field_storage_definition->isRevisionable()) {
          if ($table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
            $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage_definition);
          }
          elseif ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
            $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
          }
        }
        // Load the installed field schema so that it can be updated.
        $schema_key = "$entity_type_id.field_schema_data.$field_name";
        $field_schema_data = $entity_storage_schema_sql->get($schema_key);
  
        $processed_fields[] = [$entity_type_id, $field_name];
        // Loop over each new column and add it as a schema column change.
        foreach ($columns_to_add as $column_id) {
          $column = $table_mapping->getFieldColumnName($field_storage_definition, $column_id);
          // Add `initial_from_field` to the new spec, as this will copy over
          // the entire data.
          $field_schema = $field_item_class::schema($field_storage_definition);
          $spec = $field_schema['columns'][$column_id];
  
          // Add the new column.
          $schema->addField($table, $column, $spec);
          if ($revision_table) {
            $schema->addField($revision_table, $column, $spec);
          }
  
          // Add the new column to the installed field schema.
          if (!empty($field_schema_data)) {
            $field_schema_data[$table]['fields'][$column] = $field_schema['columns'][$column_id];
            $field_schema_data[$table]['fields'][$column]['not null'] = FALSE;
            if ($revision_table) {
              $field_schema_data[$revision_table]['fields'][$column] = $field_schema['columns'][$column_id];
              $field_schema_data[$revision_table]['fields'][$column]['not null'] = FALSE;
            }
          }
        }
  
        // Save changes to the installed field schema.
        if (!empty($field_schema_data)) {
          $entity_storage_schema_sql->set($schema_key, $field_schema_data);
        }
        if ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
          $key = "$entity_type_id.field_storage_definitions";
          if ($definitions = $entity_definitions_installed->get($key)) {
            $definitions[$field_name] = $field_storage_definition;
            $entity_definitions_installed->set($key, $definitions);
          }
        }
      }
    }
  }
  
  /**
   * Helper function for HOOK_Update to update the field schema to current, preserving existing data
   *
   * @param $field_type The field type id e.g. "iframe"
   * @param array $column
   */
  public static function _field_type_schema_column_spec_change_helper($field_type) {
    $processed_fields = [];
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $field_definition = $field_type_manager->getDefinition($field_type);
    $field_item_class = $field_definition['class'];
  
    $schema = \Drupal::database()->schema();
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $entity_field_map = $entity_field_manager->getFieldMapByFieldType($field_type);
    // The key-value collection for tracking installed storage schema.
    $entity_storage_schema_sql = \Drupal::keyValue('entity.storage_schema.sql');
    $entity_definitions_installed = \Drupal::keyValue('entity.definitions.installed');
  
    foreach ($entity_field_map as $entity_type_id => $field_map) {
      $entity_storage = $entity_type_manager->getStorage($entity_type_id);
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
      /** @var Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $entity_storage->getTableMapping($field_storage_definitions);
      // Only need field storage definitions of our field type:
      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
      foreach (array_intersect_key($field_storage_definitions, $field_map) as $field_storage_definition) {
        $field_name = $field_storage_definition->getName();
        $tables = [];
        try {
          $table = $table_mapping->getFieldTableName($field_name);
          $tables[] = $table;
        } catch (SqlContentEntityStorageException $e) {
          // Custom storage? Broken site? No matter what, if there is no table
          // there's little we can do.
          continue;
        }
        // See if the field has a revision table.
        $revision_table = NULL;
        if ($entity_type->isRevisionable() && $field_storage_definition->isRevisionable()) {
          if ($table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
            $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage_definition);
            $tables[] = $revision_table;
          }
          elseif ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
            $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
            $tables[] = $revision_table;
          }
        }
  
        $database = \Drupal::database();
        $existing_data = [];
        foreach ($tables as $table) {
          // Create any new fields from the field schema
          // if they don't exist in the database table.
          $field_schema = $field_storage_definition->getSchema();
          $columns = $table_mapping->getColumnNames($field_name);
          if (!empty($field_schema['columns'])) {
            foreach ($field_schema['columns'] as $column_name => $column_data) {
              $table_exists = $schema->tableExists($table);
              if ($table_exists) {
                $field_exists = $schema->fieldExists($table, $columns[$column_name]);
                if ($field_exists === FALSE) {
                  $schema->addField($table, $columns[$column_name], $column_data);
                }
              }
            }

            // Caches have to be cleared first to ensure
            // new fields are detected in the code.
            drupal_flush_all_caches();
          }

          // Get the old data.
          $existing_data[$table] = $database->select($table)
            ->fields($table)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
  
          // Wipe it.
          $database->truncate($table)->execute();
        }
  
        $manager = \Drupal::entityDefinitionUpdateManager();
        $manager->updateFieldStorageDefinition($manager->getFieldStorageDefinition($field_name, $entity_type_id));
  
  
        // Restore the data.
        foreach ($tables as $table) {
          if (
            !isset($existing_data[$table])
            || !is_array($existing_data[$table])
            || !count($existing_data[$table])
          ) {
            continue;
          }
          $last_row = end($existing_data[$table]);
          if ($last_row == false) {
            continue;
          }
          $fields = array_keys($last_row);
          $insert_query = $database
            ->insert($table)
            ->fields($fields);
          foreach ($existing_data[$table] as $row) {
            $insert_query->values(array_values($row));
          }
          $insert_query->execute();
        }
      }
    }
  }
  
  /**
   * Helper function for HOOK_Update to remove columns from the field schema.
   *
   * @param $field_type The field type id e.g. "drowl_paragraphs_settings"
   * @param array $columns_to_remove array of the column names from schema() function, e.g. ["style_textalign"]
   */
  public static function _field_type_schema_column_remove_helper($field_type, array $columns_to_remove = array()) {
    $processed_fields = [];
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $field_definition = $field_type_manager->getDefinition($field_type);
    $field_item_class = $field_definition['class'];
  
    $schema = \Drupal::database()->schema();
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $entity_field_map = $entity_field_manager->getFieldMapByFieldType($field_type);
    // The key-value collection for tracking installed storage schema.
    $entity_storage_schema_sql = \Drupal::keyValue('entity.storage_schema.sql');
    $entity_definitions_installed = \Drupal::keyValue('entity.definitions.installed');
  
    foreach ($entity_field_map as $entity_type_id => $field_map) {
      $entity_storage = $entity_type_manager->getStorage($entity_type_id);
  
      // Only SQL storage based entities are supported / throw known exception.
      //    if (!($entity_storage instanceof SqlContentEntityStorage)) {
      //      continue;
      //    }
  
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
      /** @var Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $entity_storage->getTableMapping($field_storage_definitions);
      // Only need field storage definitions of our field type:
      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
      foreach (array_intersect_key($field_storage_definitions, $field_map) as $field_storage_definition) {
        $field_name = $field_storage_definition->getName();
        try {
          $table = $table_mapping->getFieldTableName($field_name);
        } catch (SqlContentEntityStorageException $e) {
          // Custom storage? Broken site? No matter what, if there is no table
          // or column, there's little we can do.
          continue;
        }
        // See if the field has a revision table.
        $revision_table = NULL;
        if ($entity_type->isRevisionable() && $field_storage_definition->isRevisionable()) {
          if ($table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
            $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage_definition);
          }
          elseif ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
            $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
          }
        }
        // Load the installed field schema so that it can be updated.
        $schema_key = "$entity_type_id.field_schema_data.$field_name";
        $field_schema_data = $entity_storage_schema_sql->get($schema_key);
  
        $processed_fields[] = [$entity_type_id, $field_name];
        // Loop over each new column and add it as a schema column change.
        foreach ($columns_to_remove as $column_id) {
          $column = $table_mapping->getFieldColumnName($field_storage_definition, $column_id);
          // Add `initial_from_field` to the new spec, as this will copy over
          // the entire data.
          $field_schema = $field_item_class::schema($field_storage_definition);
          $spec = $field_schema['columns'][$column_id];
  
          // Add the new column.
          $schema->dropField($table, $column);
          if ($revision_table) {
            $schema->dropField($revision_table, $column);
          }
  
          // Remove the column from the installed field schema.
          if (!empty($field_schema_data)) {
            unset($field_schema_data[$table]['fields'][$column]);
            if ($revision_table) {
              unset($field_schema_data[$revision_table]['fields'][$column]);
            }
          }
        }
  
        // Save changes to the installed field schema.
        if (!empty($field_schema_data)) {
          $entity_storage_schema_sql->set($schema_key, $field_schema_data);
        }
        if ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
          $key = "$entity_type_id.field_storage_definitions";
          if ($definitions = $entity_definitions_installed->get($key)) {
            $definitions[$field_name] = $field_storage_definition;
            $entity_definitions_installed->set($key, $definitions);
          }
        }
      }
    }
  }

}
