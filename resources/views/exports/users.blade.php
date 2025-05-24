<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        .container {
            max-width: 100%;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .verified {
            color: #28a745;
            font-weight: bold;
        }
        .not-verified {
            color: #dc3545;
        }
        .roles {
            font-size: 9px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Users List</h1>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Email Verified</th>
                    <th>Roles</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->email_verified_at)
                            <span class="verified">✓ Verified</span>
                        @else
                            <span class="not-verified">✗ Not Verified</span>
                        @endif
                    </td>
                    <td class="roles">
                        @if($user->roles && $user->roles->count() > 0)
                            {{ $user->roles->pluck('name')->implode(', ') }}
                        @else
                            No Roles
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <p>Generated on {{ now()->format('Y-m-d H:i:s') }} | Total Users: {{ $users->count() }}</p>
        </div>
    </div>
</body>
</html>
