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




    public function update(Request $request, $id)
    {
        try {
            // Find the link by ID
            $link = Link::findOrFail($id);

            // Check if the authenticated user is the owner of the link
            if ($link->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'You are not authorized to update this link.'
                ], 403); // Forbidden
            }

            // Validate the incoming request data
            $validatedData = $request->validate([
                'destination_url' => 'required|url',
                'custom_url' => 'nullable|string|unique:links,short_url,' . $link->id,
                'tags' => 'nullable|string',
            ], [
                'custom_url.unique' => 'The custom URL is already in use. Please choose a different one.',
            ]);

            // If custom URL is provided, use it; otherwise, generate a new short URL
            $shortUrl = $validatedData['custom_url']
                ?? substr(hash('sha256', $validatedData['destination_url'] . microtime()), 0, 4);

            // Update the link
            $link->update([
                'destination_url' => $validatedData['destination_url'],
                'short_url' => $shortUrl,
                'tags' => $validatedData['tags'],
            ]);

            // Return a JSON response for success using LinkResource
            return new LinkResource($link);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Link not found
            return response()->json([
                'error' => 'Link not found.'
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database errors (e.g., unique constraint violation)
            if ($e->getCode() === '23000') {
                return response()->json([
                    'error' => 'The custom URL is already in use. Please choose a different one.'
                ], 400);
            }
            return response()->json([
                'error' => 'An error occurred while updating the link. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'error' => 'An error occurred. Please try again.'
            ], 500);
        }
    }



    public function destroy(Link $link)
    {
        // Ensure that the user owns the link (optional)
        if ($link->user_id !== auth()->id()) {
            return response()->json(['error' => 'You are not authorized to delete this link.'], 403);
        }

        // Delete the link
        $link->delete();

        // Return a JSON response with success status and the deleted link details
        return response()->json([
            'success' => true,
            'message' => 'Link deleted successfully',
            'data' => new LinkResource($link)
        ], 200);
    }


}
