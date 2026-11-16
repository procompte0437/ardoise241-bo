<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Food;
use App\Models\Person;
use App\Services\LessyStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LessyController extends Controller
{
    public function __construct(private LessyStore $store) {}

    public function state(): JsonResponse
    {
        $this->store->ensureInstalled();

        if ($this->store->isLocked()) {
            return response()->json([
                'locked' => true,
                'names' => [],
                'foods' => [],
                'maxNames' => LessyStore::MAX_NAMES,
                'maxFoods' => LessyStore::MAX_FOODS,
            ]);
        }

        return response()->json([
            'locked' => false,
            'names' => $this->store->peoplePayload(),
            'foods' => $this->store->foodsPayload(),
            'maxNames' => LessyStore::MAX_NAMES,
            'maxFoods' => LessyStore::MAX_FOODS,
        ]);
    }

    public function unlock(Request $request): JsonResponse
    {
        $this->store->ensureInstalled();

        if (! $this->store->unlock((string) $request->input('code', ''))) {
            return response()->json(['message' => 'Code incorrect.'], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function login(Request $request): JsonResponse
    {
        $this->store->ensureInstalled();

        if ($this->store->isLocked()) {
            return response()->json([
                'locked' => true,
                'message' => "La période d'essai est terminée.",
            ], 423);
        }

        if (! $this->store->checkAdmin((string) $request->input('code', ''))) {
            return response()->json(['message' => 'Code incorrect.'], 422);
        }

        return response()->json([
            'ok' => true,
            'token' => $this->store->issueAdminToken(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->header('X-Admin-Token');
        $this->store->forgetAdminToken(is_string($token) ? $token : null);

        return response()->json(['ok' => true]);
    }

    public function person(Person $person): JsonResponse
    {
        return response()->json($this->store->personPayload($person));
    }

    public function session(Request $request, Person $person): JsonResponse
    {
        $counts = $request->input('counts', []);
        if (! is_array($counts)) {
            $counts = [];
        }

        try {
            $result = $this->store->addSession($person, $counts);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function pay(Request $request, Person $person): JsonResponse
    {
        try {
            $payload = $this->store->pay($person, (int) round((float) $request->input('amount', 0)));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function erase(Request $request, Person $person): JsonResponse
    {
        try {
            $payload = $this->store->erase($person, (string) $request->input('code', ''));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function addNames(Request $request): JsonResponse
    {
        $text = (string) $request->input('text', '');
        $lines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $text) ?: [])));

        try {
            $added = $this->store->addNames($text);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $message = $added.' nom(s) ajouté(s).';
        if ($added < count($lines)) {
            $message .= ' Les doublons ou le surplus ont été ignorés.';
        }

        return response()->json([
            'added' => $added,
            'message' => $message,
            'names' => $this->store->peoplePayload(),
        ]);
    }

    public function rename(Request $request, Person $person): JsonResponse
    {
        try {
            $this->store->rename($person, (string) $request->input('name', ''));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'names' => $this->store->peoplePayload(),
        ]);
    }

    public function addFood(): JsonResponse
    {
        try {
            $this->store->addFood();
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['foods' => $this->store->foodsPayload()]);
    }

    public function updateFood(Request $request, Food $food): JsonResponse
    {
        $this->store->updateFood(
            $food,
            $request->exists('name') ? (string) $request->input('name') : null,
            $request->exists('price') ? $request->input('price') : null,
        );

        return response()->json(['food' => $this->store->foodPayload($food->fresh())]);
    }

    public function deleteFood(Food $food): JsonResponse
    {
        $this->store->deleteFood($food);

        return response()->json(['foods' => $this->store->foodsPayload()]);
    }

    public function image(Request $request, Food $food): JsonResponse
    {
        $file = $request->file('photo');
        if ($file === null) {
            return response()->json(['message' => 'Image illisible.'], 422);
        }

        try {
            $this->store->storeImage($food, $file);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['food' => $this->store->foodPayload($food->fresh())]);
    }

    public function photos(Request $request): JsonResponse
    {
        $files = $request->file('photos', []);
        if (! is_array($files) || $files === []) {
            return response()->json(['message' => 'Image illisible.'], 422);
        }

        try {
            $added = $this->store->addPhotos($files);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'added' => $added,
            'message' => $added.' image(s) ajoutée(s).',
            'foods' => $this->store->foodsPayload(),
        ]);
    }

    public function changeCode(Request $request): JsonResponse
    {
        try {
            $this->store->changeCode((string) $request->input('code', ''));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Code modifié.']);
    }
}
