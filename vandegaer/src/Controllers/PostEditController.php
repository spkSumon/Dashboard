<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\PostRepository;

/**
 * PostEditController

 * 

 * Hiermee kan je je eigen databasevelden van een bestaande post aanpassen:

 * - internal_caption

 * - internal_notes

 * - hashtags (intern)

 *
 * Endpoint (auth required):

 * - POST /api/posts/{id}/edit

 *   body: { "internal_caption": "...", "internal_notes": "...", "hashtags": "#a #b" }

 */
class PostEditController {
    private $posts;
    public function __construct(PostRepository $posts) {
        $this->posts = $posts;
    }

    public function update(int $id): void {
        $body = Request::jsonBody();
        $internalCaption = isset($body['internal_caption']) ? (string)$body['internal_caption'] : null;
        $internalNotes = isset($body['internal_notes']) ? (string)$body['internal_notes'] : null;
        $hashtags = isset($body['hashtags']) ? (string)$body['hashtags'] : null;

        $ok = $this->posts->updateInternalFields($id, $internalCaption, $internalNotes, $hashtags);
        if (!$ok) Response::error('Niet gevonden', 404);

        Response::json(['ok' => true]);
    }
}
