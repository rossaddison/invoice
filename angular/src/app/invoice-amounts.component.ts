import { Component, AfterViewInit } from '@angular/core';
import { InvoiceAmountMagnifierService } from './invoice-amount-magnifier.service';

@Component({
  selector: 'app-invoice-amounts',
  standalone: true,
  imports: [],
  template: `
    <!-- Amount magnification is handled by the service -->
  `,
  styles: [`
    ::ng-deep .amount-magnifiable {
      transition: all 0.3s ease-in-out;
      display: inline-block;
    }
    
    ::ng-deep .amount-row {
      position: relative;
    }
    
    ::ng-deep .magnified-amount {
      z-index: 1000 !important;
      position: relative;
    }
    
    /* Custom styles for different amount types */
    ::ng-deep .label-success.amount-magnifiable:hover {
      background-color: #d4edda !important;
      border-color: #28a745 !important;
    }
    
    ::ng-deep .label-warning.amount-magnifiable:hover {
      background-color: #fff3cd !important;
      border-color: #ffc107 !important;
    }
    
    ::ng-deep .label-danger.amount-magnifiable:hover {
      background-color: #f8d7da !important;
      border-color: #dc3545 !important;
    }
  `]
})
export class InvoiceAmountsComponent implements AfterViewInit {

  constructor(private magnifierService: InvoiceAmountMagnifierService) {}

  ngAfterViewInit() {
    // Reinitialize after view is ready
    setTimeout(() => {
      this.magnifierService.reinitialize();
    }, 200);
  }
}