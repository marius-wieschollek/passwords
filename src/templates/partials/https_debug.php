<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 *
 * @var $_ array
 */
?>

<div class="message report">
  <b><span class="fa fa-shield">&nbsp; &nbsp;</span>HTTPS Setup Report</b>
  <br><br>
  <table>
      <?php foreach($_['report'] as $section): ?>
        <tr>
          <th><?php p($section['label']) ?></th>
          <th>Actual</th>
          <th>Expected</th>
        </tr>
          <?php foreach($section['items'] as $item): ?>
          <tr>
            <td><?php p($item['label']) ?></td>
            <td><?php p($item['actual']) ?></td>
            <td><?php p($item['expected']) ?></td>
          </tr>
          <?php endforeach; ?>
      <?php endforeach; ?>
  </table>
</div>