<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
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