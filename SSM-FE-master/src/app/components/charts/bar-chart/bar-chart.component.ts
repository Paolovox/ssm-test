import { Component, OnInit, Input } from '@angular/core';

@Component({
  selector: 'app-bar-chart',
  templateUrl: './bar-chart.component.html',
  styleUrls: ['./bar-chart.component.scss']
})
export class BarChartComponent implements OnInit {

  @Input() color: any;
  @Input() label: any;
  @Input() data: any;
  // @Input() maxY: any;

  showChart = false;

  public barChartOptions: any = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      xAxes: [{
        display: true,
        gridLines: {
          display: false,
          drawBorder: true,
        }
      }],
      yAxes: [{
        display: true,
        ticks: {
          display: true,
          stepSize: 200,
          beginAtZero: true,
          min: 0,
          // max: this.maxY
        },
        gridLines: {
          display: true,
          drawBorder: false,
        }
      }]
    },
    legend: {
      display: false
    }
  };

  constructor() { }

  ngOnInit() {
    setTimeout(() => {
      this.showChart = true;
    }, 0);
  }
}
