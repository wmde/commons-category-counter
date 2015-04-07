<?php
if( isset( $_REQUEST['sent'] ) ) {
	require( 'CommonsFileCounter.php' );
	$cfc = new CommonsFileCounter();
	$cfc->run();
	$result = $cfc->getResult();
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<script type="text/javascript" src="res/js/jquery-2.1.1.min.js"></script>
	<script type="text/javascript" src="res/js/jquery-ui-1.10.4.custom.min.js"></script>
	<script type="text/javascript" src="res/js/jquery.jqplot.min.js"></script>
	<script type="text/javascript" src="res/js/jqplot.dateAxisRenderer.min.js"></script>

	<link href="res/css/styles.css" rel="stylesheet">
	<link href="res/css/jquery-ui-1.10.4.custom.min.css" rel="stylesheet">
	<link href="res/css/jquery.jqplot.min.css" rel="stylesheet">
	<script>
		var plotLine = [];

		$( document ).ready( function() {
			$( '#dFrom' ).datepicker( {
				dateFormat: 'yy-mm-dd',
				altField: '#dFromFormat',
				altFormat: 'yymmdd235959'
			} );
			$( '#dUntil' ).datepicker( {
				dateFormat: 'yy-mm-dd',
				altField: '#dUntilFormat',
				altFormat: 'yymmdd235959'
			} );
		} );
	</script>
</head>

<body>
    <div class="content-wrapper">
        <div class="content">
            <div class="head">
				<img src="res/img/wmde-logo.png" style="float: left; margin: 0 20px;" />
                <h1 style="line-height: 2em; text-align: center;">Commons File Counter</h1>
                <p>
					This tool determines the number of files within a
					given category and its subcategories on particular
					dates within a defined period. The dates are
					determined based on the user-defined interval.
                </p>
                <div style="clear: both;"></div>
            </div>
            <div>
                <form method="post" class="ccc-form">
                    <label>
						<span>Category:</span>
						<input type="text" name="category" class="input-wide" />
                    </label><br />
					<label>
						<span>Date from:</span>
						<input type="text" id="dFrom" readonly="readonly" />
					</label>
					<label>
						<span>Interval:</span>
						<select name="interval">
							<option>monthly</option>
							<option>weekly</option>
							<option>quarterly</option>
							<option>semiyearly</option>
							<option>yearly</option>
						</select>
					</label><br />
					<label>
						<span>Date until:</span>
						<input type="text" id="dUntil" readonly="readonly" />
					</label>
                    <input type="hidden" name="dFrom" id="dFromFormat" />
					<input type="hidden" name="dUntil" id="dUntilFormat" />
					<input type="hidden" name="sent" value="true" />
                    <button type="submit">Request</button>
					<div style="clear: both;"></div>
                </form>
            </div>
            <div class="result">
				<?php if( isset( $result ) ): ?>
					<?php $totalFiles = 0; ?>
				<table>
					<thead>
						<tr>
							<th>Date</th>
							<th>Number of Files</th>
							<th>Difference</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach( $result['files'] as $dateKey => $files ): ?>
							<?php if( $totalFiles > 0 ): ?>
								<?php $increase = count( $files ) / $totalFiles * 100; ?>
							<?php endif; ?>
							<?php $totalFiles += count( $files ); ?>
							<script>
								plotLine.push( ['<?php echo date( 'Y-m-d', strtotime( $dateKey ) ); ?>', <?php echo intval( $totalFiles ); ?>] );
							</script>
							<tr>
								<td><?php echo date( 'Y-m-d', strtotime( $dateKey ) ); ?></td>
								<td><?php echo $totalFiles; ?></td>
								<td>
									<?php if( isset( $increase ) ) : ?>
										<?php echo ( $increase >= 0 ? '+' : '-' ) . number_format( $increase, 2 ) . '%'; ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<ul>
					<li>Featured images: <?php echo $result['featured']; ?></li>
					<li>Quality images: <?php echo $result['quality']; ?></li>
					<li>Global usage: <?php echo $result['usage']; ?></li>
				</ul>

				<div id="cccChart"></div>

				<script>
					$( document ).ready( function() {
						var cccPlot = $.jqplot( 'cccChart', [ plotLine ], {
							title: 'Number of Files',
							axes: {
								xaxis: {
									renderer: $.jqplot.DateAxisRenderer
								}
							},
						});
					} );
				</script>
				<?php endif; ?>
            </div>
        </div>
    </div>
    <div class="footer">
		This tool has been developed by the software development department of Wikimedia Deutschland e. V.<br />
    </div>
</body>
</html>
