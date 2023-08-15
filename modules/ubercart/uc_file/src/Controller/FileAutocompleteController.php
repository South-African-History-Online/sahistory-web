<?php

namespace Drupal\uc_file\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileAutocompleteController {

  /**
   * Returns autocompletion content for file name textfield.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function autocompleteFilename(Request $request) {
    $matches = array();
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      $filenames = db_select('uc_files', 'f')
        ->fields('f', ['filename'])
        ->condition('filename', '%' . db_like($typed_string) . '%', 'LIKE')
        ->execute();

      while ($name = $filenames->fetchField()) {
        $matches[] = array('value' => $name);
      }
    }

    return new JsonResponse($matches);
  }

}
