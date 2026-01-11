<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('ゲーム一覧') }}
            </h2>
            <a href="{{ route('game.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                新しいゲームを作成
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div id="rooms-container">
                        @if($rooms->count() > 0)
                            <div class="grid gap-4" id="rooms-list">
                                @foreach($rooms as $room)
                                    <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700" data-room-id="{{ $room->id }}">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="text-lg font-semibold room-name">{{ $room->name }}</h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    作成者: <span class="creator-name">{{ $room->creator->name }}</span>
                                                </p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    範囲: <span class="number-range">{{ $room->min_number }} - {{ $room->max_number }}</span>
                                                </p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    参加者: <span class="participants-count">{{ $room->participants->count() }}</span> / <span class="max-players">{{ $room->max_players }}</span> 人
                                                    (最小: <span class="min-players">{{ $room->min_players }}</span> 人)
                                                </p>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="room-status px-3 py-1 rounded-full text-sm font-semibold
                                                    @if($room->status === 'waiting') bg-yellow-200 text-yellow-800
                                                    @elseif($room->status === 'playing') bg-green-200 text-green-800
                                                    @else bg-gray-200 text-gray-800
                                                    @endif">
                                                    @if($room->status === 'waiting') 待機中
                                                    @elseif($room->status === 'playing') プレイ中
                                                    @else 終了
                                                    @endif
                                                </span>
                                                <a href="{{ route('game.show', $room) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                    詳細を見る
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p id="no-rooms-message" class="text-gray-500 dark:text-gray-400 text-center py-8">
                                現在進行中のゲームはありません。新しいゲームを作成してください！
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const roomsListUrl = '{{ route('game.list') }}';
        let pollingInterval;

        function getStatusClass(status) {
            if (status === 'waiting') return 'bg-yellow-200 text-yellow-800';
            if (status === 'playing') return 'bg-green-200 text-green-800';
            return 'bg-gray-200 text-gray-800';
        }

        function getStatusText(status) {
            if (status === 'waiting') return '待機中';
            if (status === 'playing') return 'プレイ中';
            return '終了';
        }

        function updateRoomsList() {
            fetch(roomsListUrl)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('rooms-container');
                    const roomsList = document.getElementById('rooms-list');
                    const noRoomsMessage = document.getElementById('no-rooms-message');

                    if (data.rooms.length === 0) {
                        if (roomsList) roomsList.remove();
                        if (!noRoomsMessage) {
                            container.innerHTML = '<p id="no-rooms-message" class="text-gray-500 dark:text-gray-400 text-center py-8">現在進行中のゲームはありません。新しいゲームを作成してください！</p>';
                        }
                        return;
                    }

                    if (noRoomsMessage) noRoomsMessage.remove();

                    if (!roomsList) {
                        const newList = document.createElement('div');
                        newList.id = 'rooms-list';
                        newList.className = 'grid gap-4';
                        container.appendChild(newList);
                    }

                    const existingRoomIds = new Set();
                    document.querySelectorAll('[data-room-id]').forEach(el => {
                        existingRoomIds.add(parseInt(el.dataset.roomId));
                    });

                    const currentRoomIds = new Set(data.rooms.map(r => r.id));

                    existingRoomIds.forEach(id => {
                        if (!currentRoomIds.has(id)) {
                            const el = document.querySelector(`[data-room-id="${id}"]`);
                            if (el) el.remove();
                        }
                    });

                    data.rooms.forEach(room => {
                        let roomElement = document.querySelector(`[data-room-id="${room.id}"]`);

                        if (roomElement) {
                            roomElement.querySelector('.participants-count').textContent = room.participants_count;

                            const statusElement = roomElement.querySelector('.room-status');
                            statusElement.className = 'room-status px-3 py-1 rounded-full text-sm font-semibold ' + getStatusClass(room.status);
                            statusElement.textContent = getStatusText(room.status);
                        } else {
                            const newRoomHtml = `
                                <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700" data-room-id="${room.id}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-lg font-semibold room-name">${room.name}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                作成者: <span class="creator-name">${room.creator_name}</span>
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                範囲: <span class="number-range">${room.min_number} - ${room.max_number}</span>
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                参加者: <span class="participants-count">${room.participants_count}</span> / <span class="max-players">${room.max_players}</span> 人
                                                (最小: <span class="min-players">${room.min_players}</span> 人)
                                            </p>
                                        </div>
                                        <div class="flex flex-col items-end gap-2">
                                            <span class="room-status px-3 py-1 rounded-full text-sm font-semibold ${getStatusClass(room.status)}">
                                                ${getStatusText(room.status)}
                                            </span>
                                            <a href="${room.url}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                詳細を見る
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            `;
                            const list = document.getElementById('rooms-list');
                            list.insertAdjacentHTML('beforeend', newRoomHtml);
                        }
                    });
                })
                .catch(error => console.error('ゲーム一覧の更新エラー:', error));
        }

        function startPolling() {
            pollingInterval = setInterval(updateRoomsList, 3000);
        }

        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        }

        startPolling();

        window.addEventListener('beforeunload', stopPolling);

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopPolling();
            } else {
                updateRoomsList();
                startPolling();
            }
        });
    </script>
</x-app-layout>
