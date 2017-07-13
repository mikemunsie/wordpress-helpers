<?

/**
 * Get's components for the specified post
 * @param $post_id {string}
 * @return [type]
 */
function getComponentData($post_id) {
  global $wpdb;

  if (!$post_id) return [];

  // First check and see if a component structure exists
  $componentStructure = get_post_meta($post_id, '_wpcomponent_structure');
  if (!$componentStructure) return [];

  // Grab the slug ids from the components and grab the component values
  $slugIds = [];
  $slugDefs = [];
  foreach ($componentStructure[0] as $item) {    
    foreach ($item["content"] as $contentItem) {
        array_push($slugIds, $contentItem["slug_ID"]);
        $slugDefs[$contentItem["slug_ID"]] = $contentItem;
    }
  }
  $slugIds = implode(",", $slugIds);
  $slugLookup = [];
  $postMeta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_id in ($slugIds)");
  foreach ($postMeta as $item) {
    $slugLookup[$item->meta_id] = array(
        value => $item->meta_value,
        type => $slugDefs[$item->meta_id]["type"]
    );
  }

  // If so, create an array of the components
  $components = array_map(function($item) use ($slugLookup) {
    $section = substr($item["file"], 0, -4);
    $values = [];
    foreach ($item["content"] as $contentItem) {
        $slugFound = $slugLookup[$contentItem["slug_ID"]];
        $value = $slugFound["value"];

        // If the slug is an image, we need to get the post info about it and return it
        if ($slugFound["type"] == "image") {
            if ($slugFound["value"] == "") {
                $value = false;
            } else {
                $value = get_post_meta($slugFound["value"], '_wp_attachment_metadata');
                if ($value) $value = $value[0]; 
            }
        }
        $values[$contentItem["slug"]] = $value;
    }
    return array(
        type => $item["folder"]."-".$section,
        values => $values
    );
  }, $componentStructure[0]);

  return $components;
}

/**
 * Get Custom Fields from postd
 * @param  $post_id {string}
 * @return array
 */
function getACF($post_id) {  
    if (!$post_id) return false;
    $fields = get_fields($post_id);
    return $fields;
}