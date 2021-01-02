<!DOCTYPE html>

<html>
  <head>
    <meta charset="utf-8" />
    <title>table2excel | rusty1s</title>

    <link rel="stylesheet" type="text/css" href="style.css"/>

    <script src="https://rusty1s.github.io/table2excel/dist/table2excel.js"></script>
  </head>

  <body>

    <table data-excel-name="A very important table">
      <thead>
        <tr>
          <th><span>1</span></th>
          <th>2</th>
          <th>3</th>
          <th>4</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th>a</th>
          <td data-timestamp="1468834644032">18.07.2016 11:37:24</td>
          <td>a2</td>
          <td>a3</td>
        </tr>
        <tr>
          <th>b</th>
          <td colspan="2">b1+b2</td>
          <td>b3</td>
        </tr>
        <tr>
          <th>c</th>
          <td rowspan="2">c1+d1</td>
          <td>c2</td>
          <td rowspan="2">c3+d3</td>
        </tr>
        <tr>
          <th>d</th>
          <td>d2</td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4">4 entries</td>
        </tr>
      </tfoot>
    </table>

    <table data-excel-name="Another table">
      <thead>
        <tr>
          <th>1</th>
          <th>2</th>
          <th>3</th>
          <th>4</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th>e</th>
          <td>e1</td>
          <td>e2</td>
          <td>e3</td>
        </tr>
        <tr>
          <th>f</th>
          <td><input type="text" value="f1" /></td>
          <td>
            <select>
              <option>f2.1</option>
              <option>f2.2</option>
              <option>f2.3</option>
            </select>
          </td>
          <td><textarea>f3</textarea></td>
        </tr>
        </tbody>
        <tfoot>
        <tr>
          <td colspan="4">2 entries</td>
        </tr>
      </tfoot>
    </table>

    <table>
  <tr>
    <th>Company</th>
    <th>Contact</th>
    <th>Country</th>
  </tr>
  <tr>
    <td>Alfreds Futterkiste</td>
    <td>Maria Anders</td>
    <td>Germany</td>
  </tr>
  <tr>
    <td>Centro comercial Moctezuma</td>
    <td>Francisco Chang</td>
    <td>Mexico</td>
  </tr>
  <tr>
    <td>Ernst Handel</td>
    <td>Roland Mendel</td>
    <td>Austria</td>
  </tr>
  <tr>
    <td>Island Trading</td>
    <td>Helen Bennett</td>
    <td>UK</td>
  </tr>
  <tr>
    <td>Laughing Bacchus Winecellars</td>
    <td>Yoshi Tannamuri</td>
    <td>Canada</td>
  </tr>
  <tr>
    <td>Magazzini Alimentari Riuniti</td>
    <td>Giovanni Rovelli</td>
    <td>Italy</td>
  </tr>
</table>


<table>
  <tr>
    <th>Company</th>
    <th>Contact</th>
    <th>Country</th>
  </tr>
  <tr>
    <td>Alfreds Futterkiste</td>
    <td>Maria Anders</td>
    <td>Germany</td>
  </tr>
  <tr>
    <td>Centro comercial Moctezuma</td>
    <td>Francisco Chang</td>
    <td>Mexico</td>
  </tr>
  <tr>
    <td>Ernst Handel</td>
    <td>Roland Mendel</td>
    <td>Austria</td>
  </tr>
  <tr>
    <td>Island Trading</td>
    <td>Helen Bennett</td>
    <td>UK</td>
  </tr>
  <tr>
    <td>Laughing Bacchus Winecellars</td>
    <td>Yoshi Tannamuri</td>
    <td>Canada</td>
  </tr>
  <tr>
    <td>Magazzini Alimentari Riuniti</td>
    <td>Giovanni Rovelli</td>
    <td>Italy</td>
  </tr>
</table>


<table>
  <tr>
    <th>Company</th>
    <th>Contact</th>
    <th>Country</th>
  </tr>
  <tr>
    <td>Alfreds Futterkiste</td>
    <td>Maria Anders</td>
    <td>Germany</td>
  </tr>
  <tr>
    <td>Centro comercial Moctezuma</td>
    <td>Francisco Chang</td>
    <td>Mexico</td>
  </tr>
  <tr>
    <td>Ernst Handel</td>
    <td>Roland Mendel</td>
    <td>Austria</td>
  </tr>
  <tr>
    <td>Island Trading</td>
    <td>Helen Bennett</td>
    <td>UK</td>
  </tr>
  <tr>
    <td>Laughing Bacchus Winecellars</td>
    <td>Yoshi Tannamuri</td>
    <td>Canada</td>
  </tr>
  <tr>
    <td>Magazzini Alimentari Riuniti</td>
    <td>Giovanni Rovelli</td>
    <td>Italy</td>
  </tr>
</table>

    <button id="export">Export to excel</button>

    <script>
      var table2excel = new Table2Excel();

      document.getElementById('export').addEventListener('click', function() {
        table2excel.export(document.querySelectorAll('table'));
      });
    </script>

  </body>
</html>