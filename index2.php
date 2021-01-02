<!DOCTYPE html>
<html lang="en">

<head>
	<title>Export Example Excel</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>

<body>
	<div class="container">
		<h2>Export Excel</h2>
		<p>Contextual classes can be used to color table rows or table cells. The classes that can be used are: .active, .success, .info, .warning, and .danger.</p>
		<table id="tbl1" class="table">
			<thead>
				<tr>
					<th>Firstname</th>
					<th>Lastname</th>
					<th>Email</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Default</td>
					<td>Defaultson</td>
					<td>def@somemail.com</td>
				</tr>
				<tr class="success">
					<td>Success</td>
					<td>Doe</td>
					<td>john@example.com</td>
				</tr>
				<tr class="danger">
					<td>Danger</td>
					<td>Moe</td>
					<td>mary@example.com</td>
				</tr>
				<tr class="info">
					<td>Info</td>
					<td>Dooley</td>
					<td>july@example.com</td>
				</tr>
				<tr class="warning">
					<td>Warning</td>
					<td>Refs</td>
					<td>bo@example.com</td>
				</tr>
				<tr class="active">
					<td>Active</td>
					<td>Activeson</td>
					<td>act@example.com</td>
				</tr>
			</tbody>
		</table>

        <table id="tbl2" class="table">
			<thead>
				<tr>
					<th>Firstname</th>
					<th>Lastname</th>
					<th>Email</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Default</td>
					<td>Defaultson</td>
					<td>def@somemail.com</td>
				</tr>
				<tr class="success">
					<td>Success</td>
					<td>Doe</td>
					<td>john@example.com</td>
				</tr>
				<tr class="danger">
					<td>Danger</td>
					<td>Moe</td>
					<td>mary@example.com</td>
				</tr>
				<tr class="info">
					<td>Info</td>
					<td>Dooley</td>
					<td>july@example.com</td>
				</tr>
				<tr class="warning">
					<td>Warning</td>
					<td>Refs</td>
					<td>bo@example.com</td>
				</tr>
				<tr class="active">
					<td>Active</td>
					<td>Activeson</td>
					<td>act@example.com</td>
				</tr>
			</tbody>
		</table>

		<button onclick="tablesToExcel(['tbl1', 'tbl2'], ['Sheet1', 'Sheet2'], 'export.xls', 'Excel')" class="btn btn-success">Export to Excel</button>
	</div>
	<!-- export js -->
	<script src="exportToExcel.js"></script>
</body>
</html>