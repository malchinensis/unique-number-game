<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $gameRoom->name }}
            </h2>
            <a href="{{ route('game.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                ゲーム一覧に戻る
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">ゲーム情報</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">作成者</dt>
                                <dd class="font-medium">{{ $gameRoom->creator->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">数値範囲</dt>
                                <dd class="font-medium">{{ $gameRoom->min_number }} - {{ $gameRoom->max_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">参加者数</dt>
                                <dd class="font-medium">
                                    <span id="participant-count">{{ $gameRoom->participants->count() }}</span> / {{ $gameRoom->max_players }} 人
                                    (最小: {{ $gameRoom->min_players }} 人)
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">ステータス</dt>
                                <dd class="font-medium">
                                    <span id="game-status" class="px-3 py-1 rounded-full text-sm
                                        @if($gameRoom->status === 'waiting') bg-yellow-200 text-yellow-800
                                        @elseif($gameRoom->status === 'playing') bg-green-200 text-green-800
                                        @else bg-gray-200 text-gray-800
                                        @endif">
                                        @if($gameRoom->status === 'waiting') 待機中
                                        @elseif($gameRoom->status === 'playing') プレイ中
                                        @else 終了
                                        @endif
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-6">
                            @if(!$isParticipant && $gameRoom->status === 'waiting')
                                <form method="POST" action="{{ route('game.join', $gameRoom) }}">
                                    @csrf
                                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        ゲームに参加する
                                    </button>
                                </form>
                            @endif

                            @if($gameRoom->creator_id === auth()->id() && $gameRoom->status === 'waiting')
                                <form method="POST" action="{{ route('game.start', $gameRoom) }}" class="mt-2">
                                    @csrf
                                    <button type="submit" class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        ゲームを開始
                                    </button>
                                </form>
                            @endif

                            @if($isParticipant && $gameRoom->status === 'playing')
                                <div id="number-input-section">
                                    <label for="selected-number" class="block text-sm font-medium mb-2">数字を選択してください</label>
                                    <input type="number" id="selected-number" min="{{ $gameRoom->min_number }}" max="{{ $gameRoom->max_number }}"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 mb-2">
                                    <button onclick="submitNumber()" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        数字を提出
                                    </button>
                                    <p id="submit-message" class="text-sm text-gray-600 dark:text-gray-400 mt-2"></p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">参加者</h3>
                        <div id="participants-list" class="space-y-2">
                            @foreach($gameRoom->participants as $participant)
                                <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <span>{{ $participant->user->name }}</span>
                                    @if($gameRoom->status === 'finished')
                                        <div class="text-right">
                                            <span class="text-sm">選択: {{ $participant->selected_number }}</span>
                                            @if($participant->is_winner)
                                                <span class="ml-2 px-2 py-1 bg-yellow-400 text-yellow-900 rounded text-sm font-bold">勝者</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div id="results-section" class="mt-6 hidden">
                            <h3 class="text-lg font-semibold mb-4">結果</h3>
                            <div id="results-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const gameRoomId = {{ $gameRoom->id }};
        const csrfToken = '{{ csrf_token() }}';
        const statusUrl = '{{ route('game.status', $gameRoom) }}';
        const submitUrl = '{{ route('game.submit', $gameRoom) }}';
        let pollingInterval;

        function updateGameStatus() {
            fetch(statusUrl)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('participant-count').textContent = data.participants_count;

                    const statusElement = document.getElementById('game-status');
                    if (data.status === 'waiting') {
                        statusElement.textContent = '待機中';
                        statusElement.className = 'px-3 py-1 rounded-full text-sm bg-yellow-200 text-yellow-800';
                    } else if (data.status === 'playing') {
                        statusElement.textContent = 'プレイ中';
                        statusElement.className = 'px-3 py-1 rounded-full text-sm bg-green-200 text-green-800';
                    } else {
                        statusElement.textContent = '終了';
                        statusElement.className = 'px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-800';
                    }

                    if (data.has_submitted) {
                        const numberInputSection = document.getElementById('number-input-section');
                        if (numberInputSection) {
                            numberInputSection.innerHTML = '<p class="text-green-600 font-semibold">数字を提出しました。結果を待っています...</p>';
                        }
                    }

                    if (data.status === 'finished' && data.results) {
                        stopPolling();
                        showResults(data.results);
                    }
                })
                .catch(error => console.error('Error fetching status:', error));
        }

        function submitNumber() {
            const number = document.getElementById('selected-number').value;
            const messageElement = document.getElementById('submit-message');

            if (!number) {
                messageElement.textContent = '数字を入力してください';
                messageElement.className = 'text-sm text-red-600 mt-2';
                return;
            }

            fetch(submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ number: parseInt(number) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageElement.textContent = '数字を提出しました！';
                    messageElement.className = 'text-sm text-green-600 mt-2';
                    document.getElementById('number-input-section').innerHTML =
                        '<p class="text-green-600 font-semibold">数字を提出しました。結果を待っています...</p>';
                } else {
                    messageElement.textContent = 'エラーが発生しました';
                    messageElement.className = 'text-sm text-red-600 mt-2';
                }
            })
            .catch(error => {
                console.error('Error submitting number:', error);
                messageElement.textContent = 'エラーが発生しました';
                messageElement.className = 'text-sm text-red-600 mt-2';
            });
        }

        function showResults(results) {
            const resultsSection = document.getElementById('results-section');
            const resultsContent = document.getElementById('results-content');

            let html = '<div class="space-y-2">';
            results.participants.forEach(participant => {
                html += `<div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                    <span>${participant.user_name}</span>
                    <div class="text-right">
                        <span class="text-sm">選択: ${participant.selected_number}</span>
                        ${participant.is_winner ? '<span class="ml-2 px-2 py-1 bg-yellow-400 text-yellow-900 rounded text-sm font-bold">勝者</span>' : ''}
                    </div>
                </div>`;
            });
            html += '</div>';

            resultsContent.innerHTML = html;
            resultsSection.classList.remove('hidden');
        }

        function startPolling() {
            pollingInterval = setInterval(updateGameStatus, 2000);
        }

        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        }

        @if($gameRoom->status !== 'finished')
            startPolling();
        @endif

        window.addEventListener('beforeunload', stopPolling);
    </script>
</x-app-layout>
