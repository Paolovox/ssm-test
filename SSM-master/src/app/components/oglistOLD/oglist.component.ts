import { Component, OnInit, ViewChild, Input, EventEmitter, Output } from '@angular/core';
import { MatPaginator } from '@angular/material/paginator';
import { MatTableDataSource, MatTable } from '@angular/material/table';
import { CdkDragDrop, moveItemInArray, transferArrayItem, CdkDragHandle } from '@angular/cdk/drag-drop';
import { SelectionModel } from '@angular/cdk/collections';

export interface OGTableEmitter {
  type: string;
  element: any;
}

export interface OGListSettings {
  columns: Array<OGListColumns>;
  actionColumns?: OGActionColumns;
  customActions?: Array<CutomActionColumns>;
  pagingData: OGListPaging;
  search: string;
  selection: Array<any>;
}

export interface OGListColumns {
    column: string;
    name: string;
    style: OGListStyleType;
    eventType?: string;
}

export interface OGActionColumns {
    selection?: boolean;
    edit?: boolean;
    delete?: boolean;
    drag?: boolean;
}

export interface CutomActionColumns {
    name: string;
    type: string;
    icon: string;
}

export interface OGListPaging {
  total: number;
  page: number;
  order: string;
  sort: string;
  pageSize: number;
}

export enum OGListStyleType {
  'BOLD' = 1,
  'NORMAL' = 2,
  'BOLD_LINK' = 3
}

@Component({
  selector: 'app-oglist',
  templateUrl: './oglist.component.html',
  styleUrls: ['./oglist.component.scss']
})
export class OGListComponent implements OnInit {

  @ViewChild('paginator')
  public paginator: MatPaginator;

  @ViewChild('table')
  table: MatTable<any>;

  dataSource = new MatTableDataSource<any>([]);
  displayedColumns = new Array<string>();
  settingsData: any;

  @Input()
  public set data(value: Array<any>) {
    this.dataSource = new MatTableDataSource<any>(value);
  }
  public get data(): Array<any> {
    return this.dataSource.data;
  }

  @Input()
  public set settings(value: OGListSettings) {
    // Settings
    this.settingsData = value;
    // Columns
    if (value.actionColumns && value.actionColumns.selection === true) {
      this.displayedColumns.push('select');
    }
    value.columns.forEach((e) => {
      this.displayedColumns.push(e.column);
    });
    this.displayedColumns.push('actions');
    if (value.actionColumns && value.actionColumns.drag === true) {
      this.displayedColumns.push('order');
    }
    this.settingsData.selection = new SelectionModel<any>(true, value.selection);
  }
  public get settings(): OGListSettings {
    return this.settingsData;
  }

  @Output()
  update = new EventEmitter();
  @Output()
  operations = new EventEmitter<OGTableEmitter>();

  constructor() { }

  ngOnInit() {
    this.dataSource.paginator = this.paginator;
  }

  operationPressed(type: string, element: any)  {
    this.operations.emit({type, element});
  }

  paging(e) {
    this.settingsData.pagingData.page = e.pageIndex + 1;
    this.settingsData.pagingData.pageSize = e.pageSize;
    this.update.emit({ type: 'paging' });
  }

  sortData(e) {
    this.settingsData.pagingData.sort = e.active;
    this.settingsData.pagingData.order = e.direction;
    this.update.emit({ type: 'sort' });
  }

  dropTable(event: CdkDragDrop<any>) {
    this.operationPressed('drag', { item: event.item, previousItem: this.dataSource.data[event.previousIndex] });
    moveItemInArray(this.dataSource.data, event.previousIndex, event.currentIndex);
    this.table.renderRows();
  }

  //  Whether the number of selected elements matches the total number of rows.
  isAllSelected() {
    const numSelected = this.settingsData.selection.selected.length;
    const numRows = this.dataSource.data.length;
    return numSelected === numRows;
  }

  // Selects all rows if they are not all selected; otherwise clear selection.
  masterToggle() {
    this.isAllSelected() ? this.settingsData.selection.clear() : this.dataSource.data.forEach(row =>
      this.settingsData.selection.select(row.id));
  }

  public clearSelection() {
    this.settingsData.selection.clear();
  }

  public firstPage() {
    this.firstPage();
  }

}
