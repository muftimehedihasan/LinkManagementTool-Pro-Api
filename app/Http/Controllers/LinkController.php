<?php
namespace App\Http\Controllers;

use App\Models\Link;
use App\Http\Resources\LinkResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    /**
     * Display a listing of the user's links.
     */
    public function index()
    {
        // Fetch the authenticated user's links with pagination
        $links = Link::where('user_id', Auth::id()) // Filter by the authenticated user
            ->orderBy('created_at', 'desc') // Sort by creation date (latest first)
            ->paginate(5); // Paginate results, 5 per page

        // Return the paginated links using LinkResource
        return LinkResource::collection($links);
    }




    public function store(Request $request)
    {
        try {
            // Validate the request inputs
            $validatedData = $request->validate([
                'destination_url' => 'required|url',
                'custom_url' => 'nullable|string|unique:links,short_url',
                'tags' => 'nullable|string',
            ], [
                'custom_url.unique' => 'The custom URL is already in use. Please choose a different one.',
            ]);

            // Generate the short URL
            $shortUrl = $validatedData['custom_url']
                ?? substr(hash('sha256', $validatedData['destination_url'] . microtime()), 0, 4); // Using a 4-character hash

            // Create the new link
            $link = Link::create([
                'destination_url' => $validatedData['destination_url'],
                'short_url' => $shortUrl,
                'tags' => $validatedData['tags'],
                'user_id' => Auth::id(),
            ]);

            // Return a JSON response for success using LinkResource
            return new LinkResource($link);

        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database errors
            if ($e->getCode() === '23000') {
                return response()->json([
                    'error' => 'The custom URL is already in use. Please choose a different one.'
                ], 400); // Return a bad request error with a specific message
            }
            return response()->json([
                'error' => 'An error occurred while creating the link. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'error' => 'An error occurred. Please try again.'
            ], 500);
        }
    }




}
