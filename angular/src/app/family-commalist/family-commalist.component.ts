import { Component, Input, Output, EventEmitter, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-family-commalist',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './family-commalist.component.html',
  styleUrl: './family-commalist.component.scss'
})
export class FamilyCommalistComponent implements OnInit, OnDestroy {
  @Input() initialValue: string = '';
  @Output() valueChange = new EventEmitter<string>();
  
  selectedNumbers: Set<number> = new Set();
  numbers: number[] = [];
  
  // Pagination for better performance
  currentPage: number = 1;
  numbersPerPage: number = 50;
  totalPages: number = 4; // 200 numbers / 50 per page
  
  constructor() {
    // Generate numbers 1-200
    this.numbers = Array.from({ length: 200 }, (_, i) => i + 1);
  }
  
  ngOnInit() {
    if (this.initialValue) {
      // Parse existing comma-separated values
      const existingNumbers = this.initialValue
        .split(',')
        .map(n => Number.parseInt(n.trim()))
        .filter(n => !Number.isNaN(n) && n >= 1 && n <= 200);
      
      this.selectedNumbers = new Set(existingNumbers);
    }
  }
  
  ngOnDestroy() {
    // Cleanup if needed
  }
  
  get paginatedNumbers(): number[] {
    const startIndex = (this.currentPage - 1) * this.numbersPerPage;
    const endIndex = startIndex + this.numbersPerPage;
    return this.numbers.slice(startIndex, endIndex);
  }
  
  get pageInfo(): string {
    const start = (this.currentPage - 1) * this.numbersPerPage + 1;
    const end = Math.min(this.currentPage * this.numbersPerPage, 200);
    return `${start}-${end} of 200`;
  }
  
  toggleNumber(num: number): void {
    if (this.selectedNumbers.has(num)) {
      this.selectedNumbers.delete(num);
    } else {
      this.selectedNumbers.add(num);
    }
    this.emitValue();
  }
  
  isSelected(num: number): boolean {
    return this.selectedNumbers.has(num);
  }
  
  clearAll(): void {
    this.selectedNumbers.clear();
    this.emitValue();
  }
  
  selectRange(start: number, end: number): void {
    for (let i = start; i <= end; i++) {
      if (i >= 1 && i <= 200) {
        this.selectedNumbers.add(i);
      }
    }
    this.emitValue();
  }
  
  selectPage(): void {
    this.paginatedNumbers.forEach(num => {
      this.selectedNumbers.add(num);
    });
    this.emitValue();
  }
  
  deselectPage(): void {
    this.paginatedNumbers.forEach(num => {
      this.selectedNumbers.delete(num);
    });
    this.emitValue();
  }
  
  goToPage(page: number): void {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
    }
  }
  
  nextPage(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }
  
  prevPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }
  
  private emitValue(): void {
    const sortedNumbers = Array.from(this.selectedNumbers).sort((a, b) => a - b);
    const commalistValue = sortedNumbers.join(', ');
    this.valueChange.emit(commalistValue);
  }
  
  get selectedCount(): number {
    return this.selectedNumbers.size;
  }
  
  get selectedNumbersArray(): number[] {
    return Array.from(this.selectedNumbers).sort((a, b) => a - b);
  }
}