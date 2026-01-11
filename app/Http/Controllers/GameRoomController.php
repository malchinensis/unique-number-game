<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\GameParticipant;
use Illuminate\Http\Request;

class GameRoomController extends Controller
{
    public function index()
    {
        $rooms = GameRoom::with(['creator', 'participants'])
            ->where('status', '!=', 'finished')
            ->latest()
            ->get();

        return view('game.index', compact('rooms'));
    }

    public function getRoomsList()
    {
        $rooms = GameRoom::with(['creator', 'participants'])
            ->where('status', '!=', 'finished')
            ->latest()
            ->get();

        return response()->json([
            'rooms' => $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'creator_name' => $room->creator->name,
                    'min_number' => $room->min_number,
                    'max_number' => $room->max_number,
                    'min_players' => $room->min_players,
                    'max_players' => $room->max_players,
                    'participants_count' => $room->participants->count(),
                    'status' => $room->status,
                    'url' => route('game.show', $room),
                ];
            }),
        ]);
    }

    public function create()
    {
        return view('game.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_number' => 'required|integer|min:1',
            'max_number' => 'required|integer|gt:min_number',
            'min_players' => 'required|integer|min:3|max:20',
            'max_players' => 'required|integer|gte:min_players|max:20',
        ]);

        $gameRoom = GameRoom::create([
            ...$validated,
            'creator_id' => auth()->id(),
        ]);

        GameParticipant::create([
            'game_room_id' => $gameRoom->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('game.show', $gameRoom);
    }

    public function show(GameRoom $gameRoom)
    {
        $gameRoom->load(['creator', 'participants.user']);

        $isParticipant = $gameRoom->participants()
            ->where('user_id', auth()->id())
            ->exists();

        return view('game.show', compact('gameRoom', 'isParticipant'));
    }

    public function join(GameRoom $gameRoom)
    {
        if ($gameRoom->status !== 'waiting') {
            return back()->with('error', 'このゲームは既に開始されています。');
        }

        $participantCount = $gameRoom->participants()->count();
        if ($participantCount >= $gameRoom->max_players) {
            return back()->with('error', '参加人数が上限に達しています。');
        }

        $existing = GameParticipant::where('game_room_id', $gameRoom->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            return back()->with('error', '既に参加しています。');
        }

        GameParticipant::create([
            'game_room_id' => $gameRoom->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('game.show', $gameRoom)
            ->with('success', 'ゲームに参加しました！');
    }

    public function start(GameRoom $gameRoom)
    {
        if ($gameRoom->creator_id !== auth()->id()) {
            return back()->with('error', 'ゲームを開始できるのは作成者のみです。');
        }

        if (!$gameRoom->canStart()) {
            return back()->with('error', '参加人数が不足しています。');
        }

        $gameRoom->update([
            'status' => 'playing',
            'started_at' => now(),
            'round' => 1,
        ]);

        return back()->with('success', 'ゲームを開始しました！');
    }

    public function submitNumber(Request $request, GameRoom $gameRoom)
    {
        if ($gameRoom->status !== 'playing') {
            return response()->json(['error' => 'ゲームは進行中ではありません。'], 400);
        }

        $validated = $request->validate([
            'number' => "required|integer|between:{$gameRoom->min_number},{$gameRoom->max_number}",
        ]);

        $participant = GameParticipant::where('game_room_id', $gameRoom->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$participant) {
            return response()->json(['error' => '参加者ではありません。'], 403);
        }

        $participant->update([
            'selected_number' => $validated['number'],
        ]);

        if ($gameRoom->allPlayersSubmitted()) {
            $this->calculateResults($gameRoom);
        }

        return response()->json(['success' => true]);
    }

    private function calculateResults(GameRoom $gameRoom)
    {
        $participants = $gameRoom->participants;

        $numberCounts = [];
        foreach ($participants as $participant) {
            $number = $participant->selected_number;
            $numberCounts[$number] = ($numberCounts[$number] ?? 0) + 1;
        }

        $uniqueNumbers = array_keys(array_filter($numberCounts, fn($count) => $count === 1));

        if (empty($uniqueNumbers)) {
            $gameRoom->update([
                'status' => 'finished',
                'finished_at' => now(),
            ]);
            return;
        }

        $maxUniqueNumber = max($uniqueNumbers);

        foreach ($participants as $participant) {
            if ($participant->selected_number === $maxUniqueNumber) {
                $participant->update([
                    'is_winner' => true,
                    'score' => $participant->score + 1,
                ]);
            }
        }

        $gameRoom->update([
            'status' => 'finished',
            'finished_at' => now(),
        ]);
    }

    public function getStatus(GameRoom $gameRoom)
    {
        $gameRoom->load(['participants.user']);

        $participant = $gameRoom->participants()
            ->where('user_id', auth()->id())
            ->first();

        return response()->json([
            'status' => $gameRoom->status,
            'participants_count' => $gameRoom->participants()->count(),
            'all_submitted' => $gameRoom->allPlayersSubmitted(),
            'has_submitted' => $participant && $participant->selected_number !== null,
            'results' => $gameRoom->status === 'finished' ? [
                'participants' => $gameRoom->participants->map(function ($p) {
                    return [
                        'user_name' => $p->user->name,
                        'selected_number' => $p->selected_number,
                'is_winner' => $p->is_winner,
                    ];
                }),
            ] : null,
        ]);
    }
}
