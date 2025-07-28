import { useState } from 'react';
import { formatDateForAPI } from '../utils/api';

interface DateRangePickerProps {
  dateFrom: string;
  dateTo: string;
  onDateChange: (dateFrom: string, dateTo: string) => void;
}

const DateRangePicker = ({ dateFrom, dateTo, onDateChange }: DateRangePickerProps) => {
  const [preset, setPreset] = useState('');

  const handlePresetChange = (presetValue: string) => {
    setPreset(presetValue);
    
    const today = new Date();
    let from = '';
    let to = '';

    switch (presetValue) {
      case 'today':
        from = to = formatDateForAPI(today.toISOString());
        break;
      
      case 'yesterday':
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        from = to = formatDateForAPI(yesterday.toISOString());
        break;
      
      case 'last7days':
        const last7 = new Date(today);
        last7.setDate(last7.getDate() - 7);
        from = formatDateForAPI(last7.toISOString());
        to = formatDateForAPI(today.toISOString());
        break;
      
      case 'last30days':
        const last30 = new Date(today);
        last30.setDate(last30.getDate() - 30);
        from = formatDateForAPI(last30.toISOString());
        to = formatDateForAPI(today.toISOString());
        break;
      
      case 'thismonth':
        const thisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        from = formatDateForAPI(thisMonth.toISOString());
        to = formatDateForAPI(today.toISOString());
        break;
      
      case 'lastmonth':
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
        from = formatDateForAPI(lastMonth.toISOString());
        to = formatDateForAPI(lastMonthEnd.toISOString());
        break;
      
      case 'custom':
        // Don't change dates for custom
        return;
      
      default:
        from = to = '';
    }

    onDateChange(from, to);
  };

  const handleDateFromChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setPreset('custom');
    onDateChange(e.target.value, dateTo);
  };

  const handleDateToChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setPreset('custom');
    onDateChange(dateFrom, e.target.value);
  };

  const clearDates = () => {
    setPreset('');
    onDateChange('', '');
  };

  return (
    <div className="space-y-2">
      {/* Preset Selector */}
      <select
        className="uadt-select w-full"
        value={preset}
        onChange={(e) => handlePresetChange(e.target.value)}
      >
        <option value="">Select Date Range</option>
        <option value="today">Today</option>
        <option value="yesterday">Yesterday</option>
        <option value="last7days">Last 7 Days</option>
        <option value="last30days">Last 30 Days</option>
        <option value="thismonth">This Month</option>
        <option value="lastmonth">Last Month</option>
        <option value="custom">Custom Range</option>
      </select>

      {/* Custom Date Inputs */}
      <div className="flex gap-2">
        <div className="flex-1">
          <input
            type="date"
            className="uadt-input w-full"
            value={dateFrom}
            onChange={handleDateFromChange}
            placeholder="From"
          />
        </div>
        <div className="flex-1">
          <input
            type="date"
            className="uadt-input w-full"
            value={dateTo}
            onChange={handleDateToChange}
            placeholder="To"
          />
        </div>
        {(dateFrom || dateTo) && (
          <button
            type="button"
            onClick={clearDates}
            className="px-2 py-1 text-sm text-wp-text-light hover:text-wp-text"
            title="Clear dates"
          >
            ×
          </button>
        )}
      </div>

      {/* Date Range Display */}
      {(dateFrom || dateTo) && (
        <div className="text-xs text-wp-text-light">
          {dateFrom && dateTo ? (
            `From ${dateFrom} to ${dateTo}`
          ) : dateFrom ? (
            `From ${dateFrom}`
          ) : (
            `Until ${dateTo}`
          )}
        </div>
      )}
    </div>
  );
};

export default DateRangePicker;
